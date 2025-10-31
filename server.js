const express = require('express');
const sqlite3 = require('sqlite3').verbose();
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;
const JWT_SECRET = 'invoicebox_secret_key';

app.use(express.json());
app.use(express.static('public'));

const db = new sqlite3.Database('./invoicebox.db');

// Authentication middleware
const authenticateToken = (req, res, next) => {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];
    
    if (!token) {
        return res.status(401).json({ error: 'Access token required' });
    }
    
    jwt.verify(token, JWT_SECRET, (err, user) => {
        if (err) return res.status(403).json({ error: 'Invalid token' });
        req.user = user;
        next();
    });
};

// Auth Routes
app.post('/api/register', async (req, res) => {
    const { email, password, role, company_name } = req.body;
    
    if (!email || !password || !role || !company_name) {
        return res.status(400).json({ error: 'All fields required' });
    }
    
    if (!['provider', 'purchaser'].includes(role)) {
        return res.status(400).json({ error: 'Invalid role' });
    }
    
    try {
        const passwordHash = await bcrypt.hash(password, 10);
        
        db.run(
            'INSERT INTO users (email, password_hash, role, company_name) VALUES (?, ?, ?, ?)',
            [email, passwordHash, role, company_name],
            function(err) {
                if (err) {
                    return res.status(400).json({ error: 'Email already exists' });
                }
                
                const token = jwt.sign(
                    { id: this.lastID, email, role, company_name },
                    JWT_SECRET,
                    { expiresIn: '24h' }
                );
                
                res.json({ 
                    token, 
                    user: { id: this.lastID, email, role, company_name } 
                });
            }
        );
    } catch (error) {
        res.status(500).json({ error: 'Server error' });
    }
});

app.post('/api/login', (req, res) => {
    const { email, password } = req.body;
    
    db.get(
        'SELECT * FROM users WHERE email = ?',
        [email],
        async (err, user) => {
            if (err || !user) {
                return res.status(401).json({ error: 'Invalid credentials' });
            }
            
            const validPassword = await bcrypt.compare(password, user.password_hash);
            if (!validPassword) {
                return res.status(401).json({ error: 'Invalid credentials' });
            }
            
            const token = jwt.sign(
                { 
                    id: user.id, 
                    email: user.email, 
                    role: user.role, 
                    company_name: user.company_name 
                },
                JWT_SECRET,
                { expiresIn: '24h' }
            );
            
            res.json({ 
                token, 
                user: { 
                    id: user.id, 
                    email: user.email, 
                    role: user.role, 
                    company_name: user.company_name 
                } 
            });
        }
    );
});

// Invoice Routes
app.post('/api/invoices', authenticateToken, (req, res) => {
    if (req.user.role !== 'provider') {
        return res.status(403).json({ error: 'Only providers can create invoices' });
    }
    
    const { purchaser_id, items, currency, due_date } = req.body;
    
    if (!purchaser_id || !items || !currency || !['UGX', 'USD', 'LYD'].includes(currency)) {
        return res.status(400).json({ error: 'Invalid invoice data' });
    }
    
    const total_amount = items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
    const invoice_number = 'INV-' + Date.now();
    
    db.serialize(() => {
        db.run(
            `INSERT INTO invoices (provider_id, purchaser_id, invoice_number, issue_date, due_date, currency, total_amount) 
             VALUES (?, ?, ?, date('now'), ?, ?, ?)`,
            [req.user.id, purchaser_id, invoice_number, due_date, currency, total_amount],
            function(err) {
                if (err) return res.status(500).json({ error: 'Failed to create invoice' });
                
                const invoiceId = this.lastID;
                const itemStmt = db.prepare(
                    'INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?)'
                );
                
                items.forEach(item => {
                    itemStmt.run([invoiceId, item.description, item.quantity, item.unit_price, item.quantity * item.unit_price]);
                });
                
                itemStmt.finalize(() => {
                    res.json({ 
                        message: 'Invoice created successfully', 
                        invoice_id: invoiceId,
                        invoice_number,
                        total_amount 
                    });
                });
            }
        );
    });
});


// Get single invoice details
/*app.get('/api/invoices/:id', authenticateToken, (req, res) => {
    const invoiceId = req.params.id;
    
    // Get invoice basic info
    db.get(
        `SELECT i.*, 
                p.company_name as provider_company, 
                pur.company_name as purchaser_company
         FROM invoices i
         LEFT JOIN users p ON i.provider_id = p.id
         LEFT JOIN users pur ON i.purchaser_id = pur.id
         WHERE i.id = ?`,
        [invoiceId],
        (err, invoice) => {
            if (err) return res.status(500).json({ error: 'Database error' });
            if (!invoice) return res.status(404).json({ error: 'Invoice not found' });
            
            // Get invoice items
            db.all(
                'SELECT * FROM invoice_items WHERE invoice_id = ?',
                [invoiceId],
                (err, items) => {
                    if (err) return res.status(500).json({ error: 'Database error' });
                    
                    res.json({ ...invoice, items });
                }
            );
        }
    );
});*/

app.get('/api/invoices', authenticateToken, (req, res) => {
    let query = '';
    let params = [];
    
    if (req.user.role === 'provider') {
        query = `
            SELECT i.*, u.company_name as purchaser_company 
            FROM invoices i 
            JOIN users u ON i.purchaser_id = u.id 
            WHERE i.provider_id = ?
        `;
        params = [req.user.id];
    } else {
        query = `
            SELECT i.*, u.company_name as provider_company 
            FROM invoices i 
            JOIN users u ON i.provider_id = u.id 
            WHERE i.purchaser_id = ?
        `;
        params = [req.user.id];
    }
    
    db.all(query, params, (err, invoices) => {
        if (err) return res.status(500).json({ error: 'Database error' });
        
        // Get items for each invoice
        const invoicesWithItems = [];
        let processed = 0;
        
        if (invoices.length === 0) {
            return res.json([]);
        }
        
        invoices.forEach(invoice => {
            db.all(
                'SELECT * FROM invoice_items WHERE invoice_id = ?',
                [invoice.id],
                (err, items) => {
                    invoicesWithItems.push({ ...invoice, items });
                    processed++;
                    
                    if (processed === invoices.length) {
                        res.json(invoicesWithItems);
                    }
                }
            );
        });
    });
});

// Payment Routes
app.post('/api/invoices/:id/payments', authenticateToken, (req, res) => {
    const { amount, payment_date, payment_method, reference_number } = req.body;
    const invoiceId = req.params.id;
    
    if (!amount || !payment_date) {
        return res.status(400).json({ error: 'Amount and payment date required' });
    }
    
    db.serialize(() => {
        // Add payment
        db.run(
            'INSERT INTO payments (invoice_id, amount, payment_date, payment_method, reference_number) VALUES (?, ?, ?, ?, ?)',
            [invoiceId, amount, payment_date, payment_method, reference_number],
            function(err) {
                if (err) return res.status(500).json({ error: 'Failed to record payment' });
                
                // Check if invoice is fully paid
                db.get(
                    `SELECT i.total_amount, COALESCE(SUM(p.amount), 0) as total_paid 
                     FROM invoices i 
                     LEFT JOIN payments p ON i.id = p.invoice_id 
                     WHERE i.id = ? 
                     GROUP BY i.id`,
                    [invoiceId],
                    (err, result) => {
                        if (err) return res.status(500).json({ error: 'Database error' });
                        
                        if (result.total_paid >= result.total_amount) {
                            db.run(
                                'UPDATE invoices SET status = "paid" WHERE id = ?',
                                [invoiceId]
                            );
                        }
                        
                        res.json({ message: 'Payment recorded successfully' });
                    }
                );
            }
        );
    });
});

app.post('/api/invoices/:id/default', authenticateToken, (req, res) => {
    const invoiceId = req.params.id;
    
    db.run(
        'UPDATE invoices SET status = "defaulted" WHERE id = ?',
        [invoiceId],
        function(err) {
            if (err) return res.status(500).json({ error: 'Failed to mark as defaulted' });
            
            res.json({ message: 'Invoice marked as defaulted' });
        }
    );
});

// Goods/Services Catalog
app.get('/api/goods-services', authenticateToken, (req, res) => {
    db.all('SELECT * FROM goods_services ORDER BY category, name', (err, rows) => {
        if (err) return res.status(500).json({ error: 'Database error' });
        res.json(rows);
    });
});


// Get all purchasers for dropdown
app.get('/api/users/purchasers', authenticateToken, (req, res) => {
    db.all(
        'SELECT id, company_name FROM users WHERE role = "purchaser" ORDER BY company_name',
        (err, rows) => {
            if (err) {
                console.error('Database error:', err);
                return res.status(500).json({ error: 'Database error' });
            }
            res.json(rows);
        }
    );
});









// Dashboard Stats
app.get('/api/dashboard/stats', authenticateToken, (req, res) => {
    const userId = req.user.id;
    const userRole = req.user.role;
    
    let statsQuery = '';
    
    if (userRole === 'provider') {
        statsQuery = `
            SELECT 
                COUNT(*) as total_invoices,
                SUM(total_amount) as total_amount,
                SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status = 'defaulted' THEN total_amount ELSE 0 END) as defaulted_amount,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
                COUNT(CASE WHEN status = 'defaulted' THEN 1 END) as defaulted_count
            FROM invoices 
            WHERE provider_id = ?
        `;
    } else {
        statsQuery = `
            SELECT 
                COUNT(*) as total_invoices,
                SUM(total_amount) as total_amount,
                SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status = 'defaulted' THEN total_amount ELSE 0 END) as defaulted_amount,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
                COUNT(CASE WHEN status = 'defaulted' THEN 1 END) as defaulted_count
            FROM invoices 
            WHERE purchaser_id = ?
        `;
    }
    
    db.get(statsQuery, [userId], (err, stats) => {
        if (err) return res.status(500).json({ error: 'Database error' });
        
        res.json(stats);
    });
});
 

// Get all payments for a purchaser
/*app.get('/api/payments', authenticateToken, (req, res) => {
    if (req.user.role !== 'purchaser') {
        return res.status(403).json({ error: 'Only purchasers can view payments' });
    }
    
    db.all(
        `SELECT p.*, i.invoice_number, u.company_name as provider_company 
         FROM payments p
         JOIN invoices i ON p.invoice_id = i.id
         JOIN users u ON i.provider_id = u.id
         WHERE i.purchaser_id = ?
         ORDER BY p.payment_date DESC`,
        [req.user.id],
        (err, rows) => {
            if (err) {
                console.error('Database error:', err);
                return res.status(500).json({ error: 'Database error' });
            }
            res.json(rows);
        }
    );
});*/

// Get all payments for a purchaser
app.get('/api/payments', authenticateToken, (req, res) => {
    if (req.user.role !== 'purchaser') {
        return res.status(403).json({ error: 'Only purchasers can view payments' });
    }
    
    console.log('Fetching payments for user:', req.user.id);
    
    db.all(
        `SELECT p.*, i.invoice_number, u.company_name as provider_company 
         FROM payments p
         JOIN invoices i ON p.invoice_id = i.id
         JOIN users u ON i.provider_id = u.id
         WHERE i.purchaser_id = ?
         ORDER BY p.payment_date DESC`,
        [req.user.id],
        (err, rows) => {
            if (err) {
                console.error('Database error:', err);
                return res.status(500).json({ error: 'Database error' });
            }
            console.log('Found payments:', rows.length);
            res.json(rows);
        }
    );
});




// Get single invoice details
app.get('/api/invoices/:id', authenticateToken, (req, res) => {
    const invoiceId = req.params.id;
    const userId = req.user.id;
    const userRole = req.user.role;
    
    // Build the base query
    let query = `
        SELECT i.*, 
               provider.company_name as provider_company, 
               purchaser.company_name as purchaser_company
        FROM invoices i
        LEFT JOIN users provider ON i.provider_id = provider.id
        LEFT JOIN users purchaser ON i.purchaser_id = purchaser.id
        WHERE i.id = ?
    `;
    
    let params = [invoiceId];
    
    // Add role-based security: users can only see their own invoices
    if (userRole === 'provider') {
        query += ' AND i.provider_id = ?';
        params.push(userId);
    } else if (userRole === 'purchaser') {
        query += ' AND i.purchaser_id = ?';
        params.push(userId);
    }
    
    // Get invoice basic info
    db.get(query, params, (err, invoice) => {
        if (err) {
            console.error('Database error:', err);
            return res.status(500).json({ error: 'Database error' });
        }
        if (!invoice) {
            return res.status(404).json({ error: 'Invoice not found or access denied' });
        }
        
        // Get invoice items
        db.all(
            'SELECT * FROM invoice_items WHERE invoice_id = ?',
            [invoiceId],
            (err, items) => {
                if (err) {
                    console.error('Database error:', err);
                    return res.status(500).json({ error: 'Database error' });
                }
                
                res.json({ ...invoice, items });
            }
        );
    });
});

// Token refresh endpoint
app.post('/api/refresh-token', authenticateToken, (req, res) => {
    // Create new token with same user data but fresh expiration
    const newToken = jwt.sign(
        { 
            id: req.user.id, 
            email: req.user.email, 
            role: req.user.role, 
            company_name: req.user.company_name 
        },
        JWT_SECRET,
        { expiresIn: '24h' }
    );
    
    res.json({ token: newToken });
});



// Analytics data for providers
app.get('/api/analytics', authenticateToken, (req, res) => {
    if (req.user.role !== 'provider') {
        return res.status(403).json({ error: 'Only providers can view analytics' });
    }

    const providerId = req.user.id;
    
    // Get status distribution
    db.all(
        `SELECT status, COUNT(*) as count, SUM(total_amount) as amount
         FROM invoices 
         WHERE provider_id = ?
         GROUP BY status`,
        [providerId],
        (err, statusData) => {
            if (err) {
                console.error('Status distribution error:', err);
                return res.status(500).json({ error: 'Database error' });
            }
            
            // Get monthly revenue for last 12 months
            db.all(
                `SELECT 
                    strftime('%Y-%m', issue_date) as month,
                    SUM(total_amount) as revenue,
                    COUNT(*) as invoice_count
                 FROM invoices 
                 WHERE provider_id = ? 
                 AND issue_date >= date('now', '-12 months')
                 GROUP BY strftime('%Y-%m', issue_date)
                 ORDER BY month`,
                [providerId],
                (err, monthlyData) => {
                    if (err) {
                        console.error('Monthly revenue error:', err);
                        return res.status(500).json({ error: 'Database error' });
                    }
                    
                    // Get top purchasers
                    db.all(
                        `SELECT 
                            u.company_name,
                            COUNT(i.id) as invoice_count,
                            SUM(i.total_amount) as total_amount
                         FROM invoices i
                         JOIN users u ON i.purchaser_id = u.id
                         WHERE i.provider_id = ?
                         GROUP BY u.id, u.company_name
                         ORDER BY total_amount DESC
                         LIMIT 5`,
                        [providerId],
                        (err, topPurchasers) => {
                            if (err) {
                                console.error('Top purchasers error:', err);
                                return res.status(500).json({ error: 'Database error' });
                            }
                            
                            res.json({
                                statusDistribution: statusData || [],
                                monthlyRevenue: monthlyData || [],
                                topPurchasers: topPurchasers || []
                            });
                        }
                    );
                }
            );
        }
    );
});














app.listen(PORT, () => {
    console.log(`InvoiceBox server running on port ${PORT}`);
});


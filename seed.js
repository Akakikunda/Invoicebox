const sqlite3 = require('sqlite3').verbose();
const bcrypt = require('bcryptjs');

const db = new sqlite3.Database('./invoicebox.db');

// Goods/Services Catalog (50+ items)
const goodsServices = [
    // Technology Services
    { name: 'Web Development', category: 'Technology' },
    { name: 'Mobile App Development', category: 'Technology' },
    { name: 'Software Consulting', category: 'Technology' },
    { name: 'IT Support', category: 'Technology' },
    { name: 'Cloud Hosting', category: 'Technology' },
    { name: 'Database Administration', category: 'Technology' },
    { name: 'Network Setup', category: 'Technology' },
    { name: 'Cybersecurity Audit', category: 'Technology' },
    
    // Professional Services
    { name: 'Legal Consultation', category: 'Professional' },
    { name: 'Accounting Services', category: 'Professional' },
    { name: 'Marketing Strategy', category: 'Professional' },
    { name: 'Business Consulting', category: 'Professional' },
    { name: 'HR Services', category: 'Professional' },
    { name: 'Tax Preparation', category: 'Professional' },
    { name: 'Financial Planning', category: 'Professional' },
    
    // Creative Services
    { name: 'Graphic Design', category: 'Creative' },
    { name: 'Video Production', category: 'Creative' },
    { name: 'Content Writing', category: 'Creative' },
    { name: 'Photography', category: 'Creative' },
    { name: 'Brand Identity', category: 'Creative' },
    { name: 'Social Media Management', category: 'Creative' },
    
    // Goods
    { name: 'Laptop Computers', category: 'Electronics' },
    { name: 'Office Furniture', category: 'Furniture' },
    { name: 'Printers', category: 'Electronics' },
    { name: 'Network Equipment', category: 'Electronics' },
    { name: 'Software Licenses', category: 'Software' },
    { name: 'Office Supplies', category: 'Supplies' },
    { name: 'Books', category: 'Education' },
    { name: 'Training Materials', category: 'Education' },
    
    // Construction & Maintenance
    { name: 'Office Renovation', category: 'Construction' },
    { name: 'Electrical Work', category: 'Maintenance' },
    { name: 'Plumbing Services', category: 'Maintenance' },
    { name: 'Cleaning Services', category: 'Maintenance' },
    { name: 'Landscaping', category: 'Maintenance' },
    
    // Additional services to reach 50+
    { name: 'Translation Services', category: 'Professional' },
    { name: 'Event Planning', category: 'Services' },
    { name: 'Catering Services', category: 'Services' },
    { name: 'Transportation Services', category: 'Logistics' },
    { name: 'Warehouse Storage', category: 'Logistics' },
    { name: 'Insurance Services', category: 'Financial' },
    { name: 'Real Estate Consultation', category: 'Professional' },
    { name: 'Market Research', category: 'Research' },
    { name: 'Data Analysis', category: 'Research' },
    { name: 'Training Workshops', category: 'Education' },
    { name: 'Technical Support', category: 'Technology' },
    { name: 'SEO Services', category: 'Marketing' },
    { name: 'Email Marketing', category: 'Marketing' },
    { name: 'Payroll Services', category: 'Professional' },
    { name: 'Recruitment Services', category: 'Professional' },
    { name: 'Legal Documentation', category: 'Professional' },
    { name: 'Architectural Design', category: 'Design' },
    { name: 'Interior Design', category: 'Design' },
    { name: 'Security Services', category: 'Services' },
    { name: 'Equipment Rental', category: 'Rental' }
];

async function generateDummyData() {
    console.log('Generating dummy data for InvoiceBox...');
    
    // Create tables
    await new Promise((resolve, reject) => {
        db.exec(`
            DROP TABLE IF EXISTS payments;
            DROP TABLE IF EXISTS invoice_items;
            DROP TABLE IF EXISTS invoices;
            DROP TABLE IF EXISTS goods_services;
            DROP TABLE IF EXISTS users;
            
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL CHECK (role IN ('provider', 'purchaser')),
                company_name VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE invoices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                provider_id INTEGER NOT NULL,
                purchaser_id INTEGER NOT NULL,
                invoice_number VARCHAR(100) UNIQUE NOT NULL,
                issue_date DATE NOT NULL,
                due_date DATE,
                currency VARCHAR(3) NOT NULL CHECK (currency IN ('UGX', 'USD', 'LYD')),
                total_amount DECIMAL(15,2) NOT NULL,
                status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'paid', 'defaulted')),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (provider_id) REFERENCES users(id),
                FOREIGN KEY (purchaser_id) REFERENCES users(id)
            );

            CREATE TABLE invoice_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_id INTEGER NOT NULL,
                description VARCHAR(255) NOT NULL,
                quantity INTEGER NOT NULL,
                unit_price DECIMAL(15,2) NOT NULL,
                line_total DECIMAL(15,2) NOT NULL,
                FOREIGN KEY (invoice_id) REFERENCES invoices(id)
            );

            CREATE TABLE payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_id INTEGER NOT NULL,
                amount DECIMAL(15,2) NOT NULL,
                payment_date DATE NOT NULL,
                payment_method VARCHAR(100),
                reference_number VARCHAR(100),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (invoice_id) REFERENCES invoices(id)
            );

            CREATE TABLE goods_services (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(100)
            );
        `, (err) => {
            if (err) reject(err);
            else resolve();
        });
    });

    // Insert goods/services
    for (const item of goodsServices) {
        await new Promise((resolve, reject) => {
            db.run(
                'INSERT INTO goods_services (name, category) VALUES (?, ?)',
                [item.name, item.category],
                (err) => {
                    if (err) reject(err);
                    else resolve();
                }
            );
        });
    }

    // Generate 412 Providers
    const providers = [];
    for (let i = 1; i <= 412; i++) {
        providers.push({
            email: `provider${i}@company.com`,
            password: 'password123',
            role: 'provider',
            company_name: `Provider Company ${i}`
        });
    }

    // Generate 176 Purchasers
    const purchasers = [];
    for (let i = 1; i <= 176; i++) {
        purchasers.push({
            email: `purchaser${i}@company.com`,
            password: 'password123',
            role: 'purchaser',
            company_name: `Purchaser Company ${i}`
        });
    }

    // Insert users
    const allUsers = [...providers, ...purchasers];
    const userIds = { providers: [], purchasers: [] };

    for (const user of allUsers) {
        const passwordHash = await bcrypt.hash(user.password, 10);
        await new Promise((resolve, reject) => {
            db.run(
                'INSERT INTO users (email, password_hash, role, company_name) VALUES (?, ?, ?, ?)',
                [user.email, passwordHash, user.role, user.company_name],
                function(err) {
                    if (err) reject(err);
                    else {
                        if (user.role === 'provider') {
                            userIds.providers.push(this.lastID);
                        } else {
                            userIds.purchasers.push(this.lastID);
                        }
                        resolve();
                    }
                }
            );
        });
    }

    // Generate 12,719 invoices
    const currencies = ['UGX', 'USD', 'LYD'];
    const statuses = ['pending', 'paid', 'defaulted'];
    
    for (let i = 1; i <= 12719; i++) {
        const providerId = userIds.providers[Math.floor(Math.random() * userIds.providers.length)];
        const purchaserId = userIds.purchasers[Math.floor(Math.random() * userIds.purchasers.length)];
        const currency = currencies[Math.floor(Math.random() * currencies.length)];
        const status = statuses[Math.floor(Math.random() * statuses.length)];
        
        // Random date within last 7 years
        const issueDate = new Date();
        issueDate.setFullYear(issueDate.getFullYear() - Math.floor(Math.random() * 7));
        issueDate.setMonth(Math.floor(Math.random() * 12));
        issueDate.setDate(Math.floor(Math.random() * 28) + 1);
        
        const dueDate = new Date(issueDate);
        dueDate.setDate(dueDate.getDate() + 30);
        
        const invoiceNumber = `INV-${2017 + Math.floor(Math.random() * 7)}-${i.toString().padStart(5, '0')}`;
        
        // Create 1-5 random items per invoice
        const numItems = Math.floor(Math.random() * 5) + 1;
        let totalAmount = 0;
        const items = [];
        
        for (let j = 0; j < numItems; j++) {
            const service = goodsServices[Math.floor(Math.random() * goodsServices.length)];
            const quantity = Math.floor(Math.random() * 10) + 1;
            const unitPrice = Math.floor(Math.random() * 1000) + 50;
            const lineTotal = quantity * unitPrice;
            totalAmount += lineTotal;
            
            items.push({
                description: service.name,
                quantity,
                unitPrice,
                lineTotal
            });
        }
        
        // Insert invoice
        await new Promise((resolve, reject) => {
            db.run(
                `INSERT INTO invoices (provider_id, purchaser_id, invoice_number, issue_date, due_date, currency, total_amount, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
                [providerId, purchaserId, invoiceNumber, issueDate.toISOString().split('T')[0], 
                 dueDate.toISOString().split('T')[0], currency, totalAmount, status],
                function(err) {
                    if (err) reject(err);
                    else {
                        const invoiceId = this.lastID;
                        
                        // Insert items
                        const itemPromises = items.map(item => {
                            return new Promise((resolveItem, rejectItem) => {
                                db.run(
                                    'INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?)',
                                    [invoiceId, item.description, item.quantity, item.unitPrice, item.lineTotal],
                                    (err) => {
                                        if (err) rejectItem(err);
                                        else resolveItem();
                                    }
                                );
                            });
                        });
                        
                        // Insert payments for paid invoices
                        if (status === 'paid') {
                            const paymentDate = new Date(issueDate);
                            paymentDate.setDate(paymentDate.getDate() + Math.floor(Math.random() * 30));
                            
                            new Promise((resolvePayment, rejectPayment) => {
                                db.run(
                                    'INSERT INTO payments (invoice_id, amount, payment_date, payment_method) VALUES (?, ?, ?, ?)',
                                    [invoiceId, totalAmount, paymentDate.toISOString().split('T')[0], 'Bank Transfer'],
                                    (err) => {
                                        if (err) rejectPayment(err);
                                        else resolvePayment();
                                    }
                                );
                            });
                        }
                        
                        Promise.all(itemPromises).then(resolve).catch(reject);
                    }
                }
            );
        });
        
        if (i % 1000 === 0) {
            console.log(`Created ${i} invoices...`);
        }
    }
    
    console.log('Dummy data generation completed!');
    console.log(`- ${goodsServices.length} goods/services created`);
    console.log(`- ${providers.length} providers created`);
    console.log(`- ${purchasers.length} purchasers created`);
    console.log(`- 12,719 invoices created`);
    
    db.close();
}

generateDummyData().catch(console.error);
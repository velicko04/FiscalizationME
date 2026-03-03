
Use FiscalizationME;

CREATE TABLE Company (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tax_id_type VARCHAR(50),
  tax_id_number VARCHAR(100),
  name VARCHAR(255),
  country VARCHAR(100),
  city VARCHAR(100),
  address VARCHAR(255),
  enu_code VARCHAR(50),
  business_unit_code VARCHAR(50),
  software_code VARCHAR(50),
  bank_account_number VARCHAR(50),
  is_issuer_in_vat BOOLEAN
) ENGINE=InnoDB;


CREATE TABLE Buyer (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tax_id_type VARCHAR(50),
  tax_id_number VARCHAR(100),
  name VARCHAR(255),
  country VARCHAR(100),
  city VARCHAR(100),
  address VARCHAR(255)
) ENGINE=InnoDB;


CREATE TABLE VatRate (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  percentage DECIMAL(5,2),
  vat_exemption_reason TEXT
) ENGINE=InnoDB;


CREATE TABLE Product (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(100),
  name VARCHAR(255),
  unit VARCHAR(50),
  vat_rate_id INT,
  CONSTRAINT fk_product_vat_rate
    FOREIGN KEY (vat_rate_id)
    REFERENCES VatRate(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE User (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT,
  name VARCHAR(255),
  email VARCHAR(255),
  operator_code VARCHAR(50),
  role VARCHAR(50),
  is_active BOOLEAN,
  CONSTRAINT fk_user_company
    FOREIGN KEY (company_id)
    REFERENCES Company(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE Invoice (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_number VARCHAR(100),
  order_number INT,
  invoice_type VARCHAR(50),
  type_of_invoice VARCHAR(50),
  issued_at DATETIME,
  tax_period VARCHAR(50),
  total_price_without_vat DECIMAL(15,2),
  payment_method_type VARCHAR(50),
  total_vat_amount DECIMAL(15,2),
  total_price_to_pay DECIMAL(15,2),
  note TEXT,
  payment_deadline VARCHAR(50),
  iic VARCHAR(255),
  iic_signature TEXT,
  company_id INT,
  buyer_id INT NULL,
  user_id INT,
  created_at DATETIME,
  CONSTRAINT fk_invoice_company
    FOREIGN KEY (company_id)
    REFERENCES Company(id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_invoice_buyer
    FOREIGN KEY (buyer_id)
    REFERENCES Buyer(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_invoice_user
    FOREIGN KEY (user_id)
    REFERENCES User(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE InvoiceItem (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT,
  product_id INT,
  quantity DECIMAL(15,4),
  unit_price DECIMAL(15,4),
  vat_rate_id INT,
  CONSTRAINT fk_invoice_item_invoice
    FOREIGN KEY (invoice_id)
    REFERENCES Invoice(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_invoice_item_product
    FOREIGN KEY (product_id)
    REFERENCES Product(id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_invoice_item_vat_rate
    FOREIGN KEY (vat_rate_id)
    REFERENCES VatRate(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE CorrectiveInvoice (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT,
  type VARCHAR(50),
  reference_iic VARCHAR(255),
  original_issue_datetime DATETIME,
  CONSTRAINT fk_corrective_invoice_invoice
    FOREIGN KEY (invoice_id)
    REFERENCES Invoice(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE Contract (
  id INT AUTO_INCREMENT PRIMARY KEY,
  contract_number VARCHAR(100),
  company_id INT,
  buyer_id INT,
  start_date DATE,
  end_date DATE,
  billing_frequency VARCHAR(20),   
  issue_day INT,                   
  status VARCHAR(20),              
  created_at DATETIME,

  CONSTRAINT fk_contract_company
    FOREIGN KEY (company_id)
    REFERENCES Company(id)
    ON DELETE RESTRICT,

  CONSTRAINT fk_contract_buyer
    FOREIGN KEY (buyer_id)
    REFERENCES Buyer(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE ContractItem (
  id INT AUTO_INCREMENT PRIMARY KEY,
  contract_id INT,
  product_id INT,
  quantity DECIMAL(15,4),
  unit_price DECIMAL(15,4),
  vat_rate_id INT,

  CONSTRAINT fk_contract_item_contract
    FOREIGN KEY (contract_id)
    REFERENCES Contract(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_contract_item_product
    FOREIGN KEY (product_id)
    REFERENCES Product(id)
    ON DELETE RESTRICT,

  CONSTRAINT fk_contract_item_vat_rate
    FOREIGN KEY (vat_rate_id)
    REFERENCES VatRate(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;


ALTER TABLE Invoice
ADD COLUMN contract_id INT NULL,
ADD CONSTRAINT fk_invoice_contract
  FOREIGN KEY (contract_id)
  REFERENCES Contract(id)
  ON DELETE SET NULL;
  



-- 1️⃣ VAT RATES
-- --------------------------------------------------
INSERT INTO VatRate (name, percentage, vat_exemption_reason)
VALUES 
('Standard 21%', 21.00, NULL),
('Reduced 7%', 7.00, NULL),
('Exempt 0%', 0.00, 'VAT exempt according to law');

-- --------------------------------------------------
-- 2️⃣ COMPANY
-- --------------------------------------------------
INSERT INTO Company (
  tax_id_type, tax_id_number, name, country, city, address,
  enu_code, business_unit_code, software_code,
  bank_account_number, is_issuer_in_vat
)
VALUES (
  'PIB',
  '12345678',
  'Tech Solutions DOO',
  'Montenegro',
  'Podgorica',
  'Bulevar Svetog Petra 12',
  'ENU001',
  'BU001',
  'SW001',
  '540-123456-78',
  TRUE
);

-- --------------------------------------------------
-- 3️⃣ BUYER
-- --------------------------------------------------
INSERT INTO Buyer (
  tax_id_type, tax_id_number, name, country, city, address
)
VALUES (
  'PIB',
  '87654321',
  'Telekom CG',
  'Montenegro',
  'Podgorica',
  'Moskovska 29'
);

-- --------------------------------------------------
-- 4️⃣ PRODUCTS
-- --------------------------------------------------
INSERT INTO Product (code, name, unit, vat_rate_id)
VALUES
('MAG001', 'Magenta Paket', 'kom', 1),
('INT001', 'Internet Paket 100MB', 'kom', 1),
('TV001', 'TV Paket Osnovni', 'kom', 2);

-- --------------------------------------------------
-- 5️⃣ USER (operator)
-- --------------------------------------------------
INSERT INTO User (
  company_id, name, email, operator_code, role, is_active
)
VALUES (
  1,
  'Andrija Velickovic',
  'andrija@test.com',
  'OP001',
  'admin',
  TRUE
);

-- --------------------------------------------------
-- 6️⃣ CONTRACT
-- --------------------------------------------------
INSERT INTO Contract (
  contract_number,
  company_id,
  buyer_id,
  start_date,
  end_date,
  billing_frequency,
  issue_day,
  status,
  created_at
)
VALUES (
  'CTR-001',
  1,
  1,
  '2026-01-01',
  '2026-12-31',
  'monthly',
  1,
  'active',
  NOW()
);

-- --------------------------------------------------
-- 7️⃣ CONTRACT ITEMS
-- --------------------------------------------------
INSERT INTO ContractItem (
  contract_id,
  product_id,
  quantity,
  unit_price,
  vat_rate_id
)
VALUES
(1, 1, 1, 50.00, 1),
(1, 2, 1, 20.00, 1);

-- --------------------------------------------------
-- 8️⃣ INVOICE (generated from contract)
-- --------------------------------------------------
INSERT INTO Invoice (
  invoice_number,
  order_number,
  invoice_type,
  type_of_invoice,
  issued_at,
  tax_period,
  total_price_without_vat,
  payment_method_type,
  total_vat_amount,
  total_price_to_pay,
  note,
  payment_deadline,
  iic,
  iic_signature,
  company_id,
  buyer_id,
  user_id,
  created_at,
  contract_id
)
VALUES (
  'INV-001',
  1,
  'REGULAR',
  'SALE',
  NOW(),
  '01/2025',
  70.00,
  'CASH',
  14.70,
  84.70,
  'Monthly subscription invoice',
  '2025-02-01',
  'IIC123456789',
  'SIGNATURE_HASH',
  1,
  1,
  1,
  NOW(),
  1
);

-- --------------------------------------------------
-- 9️⃣ INVOICE ITEMS
-- --------------------------------------------------
INSERT INTO InvoiceItem (
  invoice_id,
  product_id,
  quantity,
  unit_price,
  vat_rate_id
)
VALUES
(1, 1, 1, 50.00, 1),
(1, 2, 1, 20.00, 1);

-- --------------------------------------------------
-- 🔟 CORRECTIVE INVOICE
-- --------------------------------------------------
INSERT INTO CorrectiveInvoice (
  invoice_id,
  type,
  reference_iic,
  original_issue_datetime
)
VALUES (
  1,
  'CORRECTION',
  'IIC123456789',
  NOW()
);


-- --------------------------------------------------
-- 1️⃣ VAT RATES
-- --------------------------------------------------
INSERT INTO VatRate (name, percentage, vat_exemption_reason) VALUES
('Standard 21%', 21.00, NULL),
('Reduced 7%', 7.00, NULL),
('Exempt 0%', 0.00, 'VAT exempt according to law');

-- --------------------------------------------------
-- 2️⃣ COMPANIES
-- --------------------------------------------------
INSERT INTO Company (tax_id_type, tax_id_number, name, country, city, address, enu_code, business_unit_code, software_code, bank_account_number, is_issuer_in_vat) VALUES
('PIB', '12345678', 'Tech Solutions DOO', 'Montenegro', 'Podgorica', 'Bulevar Svetog Petra 12', 'ENU001', 'BU001', 'SW001', '540-123456-78', TRUE),
('PIB', '23456789', 'SoftNet DOO', 'Montenegro', 'Budva', 'Riva 5', 'ENU002', 'BU002', 'SW002', '540-234567-89', TRUE);

-- --------------------------------------------------
-- 3️⃣ BUYERS
-- --------------------------------------------------
INSERT INTO Buyer (tax_id_type, tax_id_number, name, country, city, address) VALUES
('PIB', '87654321', 'Telekom CG', 'Montenegro', 'Podgorica', 'Moskovska 29'),
('PIB', '76543210', 'Vodafone CG', 'Montenegro', 'Budva', 'Marina 12');

-- --------------------------------------------------
-- 4️⃣ PRODUCTS
-- --------------------------------------------------
INSERT INTO Product (code, name, unit, vat_rate_id) VALUES
('MAG001', 'Magenta Paket', 'kom', 1),
('INT001', 'Internet Paket 100MB', 'kom', 1),
('TV001', 'TV Paket Osnovni', 'kom', 2),
('INT002', 'Internet Paket 200MB', 'kom', 1),
('TV002', 'TV Paket Premium', 'kom', 1);

-- --------------------------------------------------
-- 5️⃣ USERS
-- --------------------------------------------------
INSERT INTO User (company_id, name, email, operator_code, role, is_active) VALUES
(1, 'Andrija Velickovic', 'andrija@test.com', 'OP001', 'admin', TRUE),
(2, 'Marko Petrovic', 'marko@test.com', 'OP002', 'user', TRUE);

-- --------------------------------------------------
-- 6️⃣ CONTRACTS
-- --------------------------------------------------
INSERT INTO Contract (contract_number, company_id, buyer_id, start_date, end_date, billing_frequency, issue_day, status, created_at) VALUES
('CTR-001', 1, 1, '2026-01-01', '2026-12-31', 'monthly', 1, 'active', NOW()),
('CTR-002', 1, 2, '2026-02-01', '2026-12-31', 'monthly', 5, 'active', NOW()),
('CTR-003', 2, 1, '2026-03-01', '2026-12-31', 'quarterly', 10, 'active', NOW());

-- --------------------------------------------------
-- 7️⃣ CONTRACT ITEMS
-- --------------------------------------------------
INSERT INTO ContractItem (contract_id, product_id, quantity, unit_price, vat_rate_id) VALUES
(1, 1, 1, 50.00, 1),
(1, 2, 1, 20.00, 1),
(2, 1, 2, 50.00, 1),
(2, 3, 1, 30.00, 2),
(3, 4, 1, 25.00, 1),
(3, 5, 1, 60.00, 1);

-- --------------------------------------------------
-- 8️⃣ INVOICES
-- --------------------------------------------------
INSERT INTO Invoice (invoice_number, order_number, invoice_type, type_of_invoice, issued_at, tax_period, total_price_without_vat, payment_method_type, total_vat_amount, total_price_to_pay, note, payment_deadline, iic, iic_signature, company_id, buyer_id, user_id, created_at, contract_id) VALUES
('INV-001', 1, 'REGULAR', 'SALE', NOW(), '01/2026', 70.00, 'CASH', 14.70, 84.70, 'Monthly subscription invoice', '2026-02-01', 'IIC123456789', 'SIGNATURE_HASH', 1, 1, 1, NOW(), 1),
('INV-002', 2, 'REGULAR', 'SALE', NOW(), '02/2026', 130.00, 'CARD', 27.30, 157.30, 'Monthly subscription invoice', '2026-03-01', 'IIC987654321', 'SIGNATURE_HASH2', 1, 2, 1, NOW(), 2);

-- --------------------------------------------------
-- 9️⃣ INVOICE ITEMS
-- --------------------------------------------------
INSERT INTO InvoiceItem (invoice_id, product_id, quantity, unit_price, vat_rate_id) VALUES
(1, 1, 1, 50.00, 1),
(1, 2, 1, 20.00, 1),
(2, 1, 2, 50.00, 1),
(2, 3, 1, 30.00, 2);

-- --------------------------------------------------
-- 🔟 CORRECTIVE INVOICES
-- --------------------------------------------------
INSERT INTO CorrectiveInvoice (invoice_id, type, reference_iic, original_issue_datetime) VALUES
(1, 'CORRECTION', 'IIC123456789', NOW()),
(2, 'CORRECTION', 'IIC987654321', NOW());



INSERT INTO Company (tax_id_type, tax_id_number, name, country, city, address, enu_code, business_unit_code, software_code, bank_account_number, is_issuer_in_vat) VALUES
('PIB', '34567890', 'HardNet DOO', 'Montenegro', 'Podgorica', 'Moskovska 3', 'ENU003', 'BU003', 'SW003', '540-123956-78', TRUE),
('PIB', '23456789', 'TechNet DOO', 'Montenegro', 'Tivat', 'Riva 5', 'ENU004', 'BU004', 'SW004', '540-634567-89', TRUE),
('PIB', '17892957', 'SoftwareIT DOO', 'Montenegro', 'Budva', 'Nikole Tesle 14', 'ENU005', 'BU005', 'SW005', '540-654567-89', TRUE),
('PIB', '73984857', 'CSdata DOO', 'Montenegro', 'Risan', 'Jovana Cvijica 1', 'ENU006', 'BU006', 'SW006', '540-634647-89', TRUE),
('PIB', '57867868', 'TechAI DOO', 'Montenegro', 'Pljevlja', 'Narodnih Heroja 44', 'ENU007', 'BU007', 'SW007', '540-634519-89', TRUE);


INSERT INTO Buyer (tax_id_type, tax_id_number, name, country, city, address) VALUES
('PIB', '65432109', 'Elektroprivreda CG', 'Montenegro', 'Nikšić', 'Vuka Karadžića 2'),
('PIB', '54321098', 'Crnogorski Telekom Servis', 'Montenegro', 'Herceg Novi', 'Njegoševa 15'),
('PIB', '43210987', 'Luka Bar', 'Montenegro', 'Bar', 'Obala 13 Jula bb'),
('PIB', '32109876', 'Plantaže 13. Jul', 'Montenegro', 'Podgorica', 'Cetinjski put bb'),
('PIB', '21098765', 'Aqua Mont Service', 'Montenegro', 'Kotor', 'Stari Grad 3'),
('PIB', '10987654', 'Adriatic Trade', 'Montenegro', 'Tivat', 'Jadranska Magistrala 45'),
('PIB', '99887766', 'Montenegro Logistic', 'Montenegro', 'Danilovgrad', 'Industrijska Zona bb'),
('PIB', '88776655', 'Primorje Invest', 'Montenegro', 'Ulcinj', 'Bulevar Teuta 21');



ALTER TABLE Product
ADD COLUMN price DECIMAL(15,4) NOT NULL DEFAULT 0;


UPDATE Product SET price = 50.00 WHERE id = 1;
UPDATE Product SET price = 20.00 WHERE id = 2;
UPDATE Product SET price = 30.00 WHERE id = 3;
UPDATE Product SET price = 25.00 WHERE id = 4;
UPDATE Product SET price = 60.00 WHERE id = 5;
UPDATE Product SET price = 30.00 WHERE id = 6;
UPDATE Product SET price = 40.00 WHERE id = 7;
UPDATE Product SET price = 50.00 WHERE id = 8;







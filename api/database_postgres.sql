-- Fichier SQL compatible avec PostgreSQL

-- Création de la table des utilisateurs
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL CHECK (role IN ('Admin', 'Manager', 'Employe')),
    session_token VARCHAR(255) NULL,
    token_expires_at TIMESTAMP NULL
);

-- Création de la table des produits
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(10, 2) NOT NULL
);

-- Création de la table des mouvements de stock
CREATE TABLE stock_movements (
    id SERIAL PRIMARY KEY,
    product_id INT,
    movement_type VARCHAR(10) NOT NULL CHECK (movement_type IN ('IN', 'OUT')),
    quantity INT NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insertion d'un utilisateur Admin par défaut pour les tests
INSERT INTO users (username, password, role) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin');

-- Insertion de quelques produits pour l'exemple
INSERT INTO products (name, description, quantity, price) VALUES 
('Laptop Pro', 'Un ordinateur portable puissant pour les professionnels', 50, 1200.00),
('Souris Gamer', 'Souris avec haute précision pour le gaming', 200, 75.50),
('Clavier Mécanique', 'Clavier mécanique rétroéclairé', 150, 110.20);

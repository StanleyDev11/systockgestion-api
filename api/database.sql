-- Création de la table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Le mot de passe sera haché
    role ENUM('Admin', 'Manager', 'Employe') NOT NULL,
    session_token VARCHAR(255) NULL, -- Token de session pour l'authentification
    token_expires_at DATETIME NULL -- Date d'expiration du token
);

-- Création de la table des produits
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(10, 2) NOT NULL
);

-- Création de la table des mouvements de stock
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    movement_type ENUM('IN', 'OUT') NOT NULL,
    quantity INT NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insertion d'un utilisateur Admin par défaut pour les tests
-- Le mot de passe pour 'admin' est 'password'. Assurez-vous de le hacher lors de la création de vrais utilisateurs.
-- Pour l'exemple, nous insérons un mot de passe haché pour 'password'
-- Vous pouvez générer un hachage avec password_hash('password', PASSWORD_DEFAULT); en PHP
INSERT INTO users (username, password, role) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin');

-- Insertion de quelques produits pour l'exemple
INSERT INTO products (name, description, quantity, price) VALUES 
('Laptop Pro', 'Un ordinateur portable puissant pour les professionnels', 50, 1200.00),
('Souris Gamer', 'Souris avec haute précision pour le gaming', 200, 75.50),
('Clavier Mécanique', 'Clavier mécanique rétroéclairé', 150, 110.20);

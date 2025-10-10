DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS products;

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name   VARCHAR(120) NOT NULL,
  brand  VARCHAR(60)  NOT NULL,
  price  DECIMAL(8,2) NOT NULL,
  flavor  VARCHAR(30) NOT NULL,
  image  VARCHAR(255),
  stock  INT NOT NULL DEFAULT 0
);

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(200) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer'
);

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name  VARCHAR(100),
  last_name   VARCHAR(100),
  email       VARCHAR(200),
  address     VARCHAR(200),
  postal_code VARCHAR(20),
  city        VARCHAR(100),
  country     VARCHAR(100),
  total       DECIMAL(10,2),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id   INT NOT NULL,
  product_id INT NOT NULL,
  quantity   INT NOT NULL,
  unit_price DECIMAL(8,2) NOT NULL,
  FOREIGN KEY (order_id)  REFERENCES orders(id)   ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- demo data
INSERT INTO products (name,brand,price,flavor,image,stock) VALUES
('Coca-Cola Original 330ml','Coca-Cola',0.89,'cola','coca_cola_330.png',100),
('Fanta Orange 330ml','Fanta',0.85,'fanta','fanta_orange_330.png',100),
('Pepsi Max 330ml','PepsiCo',0.85,'pepsi','pepsi_max_330.png',100),
('7UP 500ml','7UP',1.59,'7UP','7up_500.png',100),
('Sprite 330ml','Sprite',0.85,'citroen','sprite_330.png',100),
('Lipton Ice Tea Lemon 500ml','ice tea lemon',1.69,'lemon','lipton_lemon_500.png',100),
('Red Bull 250ml','Red Bull',1.69,'watermaloen','redbull_250.png',100),
('Monster Energy 500ml','Monster',2.19,'zoete citrusvruchten','monster_500.png',100),
('Dr Pepper 330ml','Dr Pepper',0.99,'fruitige smaken','drpepper_330.png',100),
('Fernandes Green Punch 330ml','Fernandes',1.05,'fruitcocktail','fernandes_green_330.png',100);

-- admin met wachtwoord 'admin123'
INSERT INTO users (email,password_hash,role) VALUES
('admin@shop.local', '$2y$10$JtdVLWEzqZ2H9M6nJ2d6VOMtV1sHTmP8tS9UQJ7l3G2x3QPGfH0t2', 'admin');

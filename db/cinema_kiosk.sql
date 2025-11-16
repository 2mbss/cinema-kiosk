-- Cinema Kiosk Database Schema
CREATE DATABASE IF NOT EXISTS cinema_kiosk;
USE cinema_kiosk;

-- Admin users table
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Movies table
CREATE TABLE movies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    trailer_url VARCHAR(500),
    poster_image VARCHAR(255),
    duration INT, -- in minutes
    rating VARCHAR(10),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Showtimes table
CREATE TABLE showtimes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    movie_id INT,
    show_date DATE NOT NULL,
    show_time TIME NOT NULL,
    total_seats INT DEFAULT 50,
    available_seats INT DEFAULT 50,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- Seats table
CREATE TABLE seats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    showtime_id INT,
    seat_number VARCHAR(10) NOT NULL,
    is_booked BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id) ON DELETE CASCADE
);

-- Extras (snacks/drinks) table
CREATE TABLE extras (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category ENUM('snack', 'drink') NOT NULL,
    image VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sales table for analytics
CREATE TABLE sales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    showtime_id INT,
    seats_booked INT,
    total_amount DECIMAL(10,2),
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id)
);

-- Sales extras junction table
CREATE TABLE sales_extras (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sale_id INT,
    extra_id INT,
    quantity INT,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (extra_id) REFERENCES extras(id)
);

-- Insert sample admin user (password: admin123)
INSERT INTO admins (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample movies
INSERT INTO movies (title, description, trailer_url, poster_image, duration, rating) VALUES
('Spider-Man: No Way Home', 'Peter Parker seeks help from Doctor Strange when his identity is revealed.', 'https://youtube.com/watch?v=JfVOs4VSpmA', 'spiderman.jpg', 148, 'PG-13'),
('The Batman', 'Batman ventures into Gotham City underworld when a sadistic killer leaves behind cryptic messages.', 'https://youtube.com/watch?v=mqqft2x_Aa4', 'batman.jpg', 176, 'PG-13'),
('Top Gun: Maverick', 'After thirty years, Maverick is still pushing the envelope as a top naval aviator.', 'https://youtube.com/watch?v=qSqVVswa420', 'topgun.jpg', 130, 'PG-13');

-- Insert sample showtimes
INSERT INTO showtimes (movie_id, show_date, show_time, price) VALUES
(1, '2024-01-15', '14:00:00', 12.50),
(1, '2024-01-15', '17:00:00', 15.00),
(1, '2024-01-15', '20:00:00', 15.00),
(2, '2024-01-15', '15:30:00', 12.50),
(2, '2024-01-15', '18:30:00', 15.00),
(3, '2024-01-15', '16:00:00', 12.50),
(3, '2024-01-15', '19:00:00', 15.00);

-- Insert sample extras
INSERT INTO extras (name, description, price, category, image) VALUES
('Popcorn Large', 'Large buttered popcorn', 8.50, 'snack', 'popcorn.jpg'),
('Popcorn Medium', 'Medium buttered popcorn', 6.50, 'snack', 'popcorn.jpg'),
('Coca Cola', 'Large Coca Cola drink', 5.00, 'drink', 'coke.jpg'),
('Nachos', 'Nachos with cheese dip', 7.50, 'snack', 'nachos.jpg'),
('Water Bottle', 'Bottled water', 3.00, 'drink', 'water.jpg'),
('Candy Mix', 'Assorted movie theater candy', 4.50, 'snack', 'candy.jpg');

-- Insert sample sales data for analytics
INSERT INTO sales (showtime_id, seats_booked, total_amount) VALUES
(1, 2, 25.00),
(1, 1, 12.50),
(2, 3, 45.00),
(3, 2, 30.00),
(4, 1, 12.50),
(5, 4, 60.00);

-- Insert sample sales extras
INSERT INTO sales_extras (sale_id, extra_id, quantity) VALUES
(1, 1, 1), (1, 3, 2),
(2, 2, 1), (2, 5, 1),
(3, 1, 2), (3, 4, 1),
(4, 3, 2), (4, 6, 1);
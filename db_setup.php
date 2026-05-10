<?php
// db_setup.php - Fixed Database Schema for MariaDB
// 🔐 SECURITY: Restrict access to localhost only
$allowed_ips = ['127.0.0.1', '::1', 'localhost'];
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (php_sapi_name() !== 'cli' && !in_array($client_ip, $allowed_ips)) {
    http_response_code(403);
    die('<h3>403 Forbidden</h3><p>Database setup is only accessible from localhost or CLI.</p>');
}

require_once 'config.php';


// Set timezone
date_default_timezone_set('UTC');

// Fixed schema with profile_picture field and proper ENUM handling
$sql = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    fitness_goal TEXT,
    profile_picture VARCHAR(255) DEFAULT 'default.png',
    weight DECIMAL(5,2),
    height DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    attended TINYINT(1) DEFAULT 1,
    UNIQUE KEY unique_user_date (user_id, date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    type VARCHAR(50) NOT NULL,
    duration INT NOT NULL,
    calories_burned INT,
    notes TEXT,
    mood VARCHAR(10) DEFAULT '😊',
    exercises JSON,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mood_checkins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    mood VARCHAR(10) NOT NULL,
    notes TEXT,
    starred TINYINT(1) DEFAULT 0,
    archived TINYINT(1) DEFAULT 0,
    UNIQUE KEY unique_user_date (user_id, date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trigger_word VARCHAR(50) NOT NULL,
    response TEXT NOT NULL,
    category VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS nutrition_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    meal_type ENUM('breakfast', 'lunch', 'dinner', 'snack') NOT NULL,
    food_item VARCHAR(255) NOT NULL,
    calories INT NOT NULL,
    protein DECIMAL(5,2),
    carbs DECIMAL(5,2),
    fat DECIMAL(5,2),
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_ai TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    icon VARCHAR(50) DEFAULT 'fas fa-bell',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_personas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    instructions TEXT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    method VARCHAR(50) DEFAULT 'credit_card',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



" ;

try {
    // Set foreign key checks to 0 temporarily
    $pdo->exec("SET foreign_key_checks = 0");
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET foreign_key_checks = 1");
    
    echo "<div style='padding: 20px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px;'>";
    echo "Database tables created successfully!<br>";
    
    // Check if ai_responses table is empty and insert sample data
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ai_responses");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Insert comprehensive AI responses
        $responses = [
            // Workout responses
            ['trigger' => 'workout', 'response' => 'Oh look, someone finally showed up! Here\'s your personalized punishment plan:', 'category' => 'workout'],
            ['trigger' => 'routine', 'response' => 'Your routine needs more pain. Let me fix that for you!', 'category' => 'workout'],
            ['trigger' => 'plan', 'response' => 'I\'ve analyzed your pathetic progress. Here\'s what you need:', 'category' => 'workout'],
            
            // Attendance responses
            ['trigger' => 'skip', 'response' => 'Skipping leg day again? Your future self will hate you... and so will I!', 'category' => 'attendance'],
            ['trigger' => 'miss', 'response' => 'Missing workouts? Your membership fee is funding my vacation!', 'category' => 'attendance'],
            ['trigger' => 'absent', 'response' => 'Your absence is noted... and mocked!', 'category' => 'attendance'],
            
            // Form/exercise responses
            ['trigger' => 'form', 'response' => 'Your form looks like a dying flamingo. Let\'s fix that before you injure yourself!', 'category' => 'exercise'],
            ['trigger' => 'technique', 'response' => 'Your technique is so bad, even gravity is embarrassed!', 'category' => 'exercise'],
            ['trigger' => 'posture', 'response' => 'Stand up straight! You\'re not a question mark!', 'category' => 'exercise'],
            
            // Motivation responses
            ['trigger' => 'weight', 'response' => 'You call that a workout? My grandma lifts heavier weights than that!', 'category' => 'motivation'],
            ['trigger' => 'heavy', 'response' => 'That weight is lighter than your excuses!', 'category' => 'motivation'],
            ['trigger' => 'strong', 'response' => 'Strong? You couldn\'t lift your own ego!', 'category' => 'motivation'],
            
            // Positive feedback
            ['trigger' => 'good', 'response' => 'Good job! Now do it again... and again... until your muscles cry for mercy!', 'category' => 'motivation'],
            ['trigger' => 'great', 'response' => 'Great? Don\'t get cocky. You\'re still 90% lazy!', 'category' => 'motivation'],
            ['trigger' => 'awesome', 'response' => 'Awesome? I\'ve seen better form from a sack of potatoes!', 'category' => 'motivation'],
            
            // Progress tracking
            ['trigger' => 'progress', 'response' => 'Your progress is... acceptable. I guess. Don\'t get cocky.', 'category' => 'progress'],
            ['trigger' => 'improve', 'response' => 'Improving? Barely. Keep pushing or I\'ll find someone who will!', 'category' => 'progress'],
            ['trigger' => 'results', 'response' => 'Results? What results? I see potential... for more suffering!', 'category' => 'progress'],
            
            // Nutrition advice
            ['trigger' => 'nutrition', 'response' => 'Nutrition tip: Stop eating like a garbage disposal. Your abs are hiding under that pizza!', 'category' => 'nutrition'],
            ['trigger' => 'diet', 'response' => 'Your diet looks like a food court exploded. Clean it up!', 'category' => 'nutrition'],
            ['trigger' => 'food', 'response' => 'That\'s not food, that\'s a crime against your abs!', 'category' => 'nutrition'],
            
            // Administrative
            ['trigger' => 'membership', 'response' => 'Reminder: Your membership expires in 3 days. Pay up or get out!', 'category' => 'admin'],
            ['trigger' => 'payment', 'response' => 'Your payment is late. My patience is thinner than your biceps!', 'category' => 'admin'],
            ['trigger' => 'bill', 'response' => 'The bill is due. Your sweat equity doesn\'t cover it!', 'category' => 'admin'],
            
            // Mood-related
            ['trigger' => 'mood', 'response' => 'Your mood seems low today. Remember: sweat is just fat crying!', 'category' => 'mood'],
            ['trigger' => 'sad', 'response' => 'Sad? Lift something heavy. It\'s cheaper than therapy!', 'category' => 'mood'],
            ['trigger' => 'happy', 'response' => 'Happy? Good. Now channel that energy into destroying this workout!', 'category' => 'mood'],
            
            // Consistency
            ['trigger' => 'consistent', 'response' => 'You\'ve been consistent! I\'m almost impressed... almost.', 'category' => 'attendance'],
            ['trigger' => 'regular', 'response' => 'Regular attendance? Don\'t stop now or I\'ll hunt you down!', 'category' => 'attendance'],
            ['trigger' => 'frequent', 'response' => 'Frequent workouts? Keep it up or I\'ll replace you with someone who cares!', 'category' => 'attendance'],
            
            // General sarcasm
            ['trigger' => 'help', 'response' => 'Help? I\'m not your babysitter. Lift something heavy and stop whining!', 'category' => 'general'],
            ['trigger' => 'tired', 'response' => 'Tired? Good. Now you know how your muscles feel every time you skip leg day!', 'category' => 'general'],
            ['trigger' => 'hard', 'response' => 'Hard? This is supposed to be hard! If it was easy, everyone would have abs!', 'category' => 'general']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO ai_responses (trigger_word, response, category) VALUES (?, ?, ?)");
        foreach ($responses as $response) {
            $stmt->execute([$response['trigger'], $response['response'], $response['category']]);
        }
        
        echo "Sample AI responses inserted successfully!<br>";
    } else {
        echo "AI responses already exist in database.<br>";
    }

    // Check if ai_personas table is empty and insert default personas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ai_personas");
    $stmt->execute();
    $pCount = $stmt->fetchColumn();

    if ($pCount == 0) {
        $personas = [
            ['name' => 'Drill Sergeant', 'instructions' => 'You are a tough, no-nonsense drill sergeant. You use aggressive motivation, call users "recruit" or "maggot", and have zero patience for excuses. Your goal is to push them to their physical limits through discipline.'],
            ['name' => 'Supportive Coach', 'instructions' => 'You are a warm, encouraging, and empathetic coach. You focus on positive reinforcement, mental well-being, and celebrate even the smallest wins. You treat the user like a friend on a journey.'],
            ['name' => 'Scientific Biohacker', 'instructions' => 'You are a data-driven fitness scientist. You use technical terms, cite biological facts (like muscle protein synthesis or ATP cycles), and focus on efficiency and optimization. Your tone is professional and analytical.']
        ];
        $stmt = $pdo->prepare("INSERT INTO ai_personas (name, instructions) VALUES (?, ?)");
        foreach ($personas as $p) {
            $stmt->execute([$p['name'], $p['instructions']]);
        }
        echo "Default AI personas inserted successfully!<br>";
    }
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount == 0) {
        // Create default admin user
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, fitness_goal) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Admin User', 'admin@gymcrm.com', $adminPassword, 'admin', 'Manage the gym']);
        echo "Default admin user created (email: admin@gymcrm.com, password: admin123)<br>";
    }
    
    echo "</div>";
    
} catch(PDOException $e) {
    // Re-enable foreign key checks in case of error
    $pdo->exec("SET foreign_key_checks = 1");
    echo "<div style='padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>";
    echo "Error creating tables: " . $e->getMessage();
    echo "</div>";
}
?>
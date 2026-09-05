<?php
/**
 * Setup script to initialize the database, tables, seed data, and default admin.
 */
$host = 'localhost';
$user = 'root';
$pass = '';
$dbName = 'goldgym_htd';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Create DB
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");
    echo "Database '$dbName' ready.\n";

    // Ensure tables exist
    $schemaFile = __DIR__ . '/sql/schema.sql';
    if (file_exists($schemaFile)) {
        $sql = file_get_contents($schemaFile);
        $sql = str_replace('CREATE TABLE ', 'CREATE TABLE IF NOT EXISTS ', $sql);
        try {
            $pdo->exec($sql);
            echo "Schema tables verified.\n";
        } catch (PDOException $ex) {
            // tables might already exist
        }
    }

    // Ensure default admin exists
    $adminUser = 'admin';
    $adminEmail = 'admin@goldgym.com';
    $adminPass = 'admin123';
    $hash = password_hash($adminPass, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = :email OR username = :username");
    $stmt->execute(['email' => $adminEmail, 'username' => $adminUser]);
    if ($stmt->fetch()) {
        $update = $pdo->prepare("UPDATE admins SET password_hash = :p WHERE username = :u");
        $update->execute(['p' => $hash, 'u' => $adminUser]);
        echo "Admin account password updated to '$adminPass'.\n";
    } else {
        $insert = $pdo->prepare("INSERT INTO admins (username, email, password_hash) VALUES (:u, :e, :p)");
        $insert->execute(['u' => $adminUser, 'e' => $adminEmail, 'p' => $hash]);
        echo "Default admin created: Username '$adminUser', Email '$adminEmail', Password '$adminPass'.\n";
    }

    // Add rich sample content if tables are empty
    // 1. Why choose us
    $whyCount = $pdo->query("SELECT COUNT(*) FROM why_choose_us")->fetchColumn();
    if ($whyCount == 0) {
        $pdo->exec("INSERT INTO why_choose_us (icon, title, description, display_order) VALUES
            ('⚡', 'High-End Equipment', 'State-of-the-art free weights, Olympic lifting platforms, and modern cardio stations.', 1),
            ('🏆', 'Certified Trainers', 'Expert coaches providing tailored guidance for hypertrophy, powerlifting, and fat loss.', 2),
            ('🔥', 'Passionate Community', 'A motivating, focused atmosphere where members push and support each other every day.', 3)
        ");
        echo "Inserted 'Why choose us' sample data.\n";
    }

    // 2. Services
    $servCount = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
    if ($servCount == 0) {
        $pdo->exec("INSERT INTO services (title, description, price, display_order) VALUES
            ('Weight Training & Bodybuilding', 'Full access to dumbbell arrays, barbell cages, cable towers, and hammer strength machines.', 'Rs. 2,000 / mo', 1),
            ('Personal Coaching & Diet Plans', '1-on-1 personalized training schedules and macro-balanced nutritional consulting.', 'Custom Plans', 2),
            ('Cardio & HIIT Conditioning', 'Treadmills, rowers, assault bikes, and high-intensity interval training for stamina.', 'Included in Membership', 3)
        ");
        echo "Inserted 'Services' sample data.\n";
    }

    // 3. Training Programs
    $progCount = $pdo->query("SELECT COUNT(*) FROM training_programs")->fetchColumn();
    if ($progCount == 0) {
        $pdo->exec("INSERT INTO training_programs (title, description, duration, display_order) VALUES
            ('12-Week Muscle Hypertrophy', 'Progressive overload training designed to maximize muscle growth and functional symmetry.', '12 Weeks', 1),
            ('Strength & Powerlifting Prep', 'Technique-focused deadlift, squat, and bench press periodization cycles.', '8 Weeks', 2),
            ('Body Transformation & Fat Shred', 'High-volume circuit conditioning paired with caloric deficit guidance.', '6 Weeks', 3)
        ");
        echo "Inserted 'Training Programs' sample data.\n";
    }

    // 4. Membership Plans
    $planCount = $pdo->query("SELECT COUNT(*) FROM membership_plans")->fetchColumn();
    if ($planCount == 0) {
        $pdo->exec("INSERT INTO membership_plans (name, price, duration, description, features, button_text, is_featured, display_order) VALUES
            ('Monthly Pass', 'Rs. 2,000', 'Month', 'Flexible monthly training pass with full gym floor access.', 'Full Gym Access\nLocker Room & Showers\nFree Fitness Assessment\nCardio & Weight Areas', 'Get Started', 0, 1),
            ('Quarterly Pro', 'Rs. 5,500', '3 Months', 'Our most popular choice for committed athletes seeking steady gains.', 'Full Gym & Cardio Access\n1 Free Personal Training Session\nCustom Nutrition Guide\nPriority Locker Access', 'Join Pro Plan', 1, 2),
            ('Annual Elite', 'Rs. 18,000', 'Year', 'Year-round elite access with maximum savings and VIP coaching discounts.', 'Unlimited 365-Day Access\n4 Personal Training Sessions\nQuarterly Body Composition Scans\nGold Gym Apparel & Shaker', 'Join Elite', 0, 3)
        ");
        echo "Inserted 'Membership Plans' sample data.\n";
    }

    // 5. Trainers
    $trainCount = $pdo->query("SELECT COUNT(*) FROM trainers")->fetchColumn();
    if ($trainCount == 0) {
        $pdo->exec("INSERT INTO trainers (name, position, specialization, phone, display_order) VALUES
            ('Senior Coach', 'Head Strength Trainer', 'Powerlifting, Strength Periodization, Injury Prevention', '9845296796', 1),
            ('Fitness Specialist', 'Body Transformation Coach', 'Hypertrophy, Functional Mobility, Fat Loss', '9845296796', 2)
        ");
        echo "Inserted 'Trainers' sample data.\n";
    }

    echo "\n=== ALL DONE! ===\n";
    echo "Login to Admin at: http://localhost/goldgym3/admin/login.html\n";
    echo "Username / Email: admin@goldgym.com (or admin)\n";
    echo "Password: admin123\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

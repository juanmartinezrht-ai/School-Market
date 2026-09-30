<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getConnection();

    // Check count of users
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();

    echo "User count before seed: " . $count . "\n";

    if ($count == 0) {
        $passwordHash = password_hash('password123', PASSWORD_DEFAULT);

        // Seed 3 test accounts
        $users = [
            [
                'full_name' => 'Camila Rodríguez',
                'student_id' => 'EST-101',
                'email' => 'estudiante@colegio.edu.co',
                'password' => $passwordHash,
                'school_id' => 6, // Colegio Calasanz
                'grade_level' => '10°',
                'group_name' => '10-A',
                'role' => 'student'
            ],
            [
                'full_name' => 'Mateo Silva',
                'student_id' => 'EST-102',
                'email' => 'mateo@colegio.edu.co',
                'password' => $passwordHash,
                'school_id' => 1, // Colegio San José de Las Vegas
                'grade_level' => '11°',
                'group_name' => '11-B',
                'role' => 'student'
            ],
            [
                'full_name' => 'Prof. Carlos Mendoza',
                'student_id' => null,
                'email' => 'profesor@colegio.edu.co',
                'password' => $passwordHash,
                'school_id' => 6, // Colegio Calasanz
                'grade_level' => null,
                'group_name' => null,
                'role' => 'teacher'
            ]
        ];

        $ins = $db->prepare("INSERT INTO users (full_name, student_id, email, password, school_id, grade_level, group_name, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($users as $u) {
            $ins->execute([
                $u['full_name'], $u['student_id'], $u['email'], $u['password'],
                $u['school_id'], $u['grade_level'], $u['group_name'], $u['role']
            ]);
            echo "Created user: " . $u['email'] . "\n";
        }
    }

    // Also check student products count
    $stmtProd = $db->query("SELECT COUNT(*) FROM student_products");
    $prodCount = $stmtProd->fetchColumn();
    echo "Product count before seed: " . $prodCount . "\n";

    if ($prodCount == 0) {
        // Get user 1 ID
        $user1 = $db->query("SELECT id FROM users LIMIT 1")->fetchColumn();
        if ($user1) {
            $prods = [
                [
                    'user_id' => $user1,
                    'school_id' => 6, // Calasanz
                    'title' => 'Brownies Artesanales con Arequipe',
                    'description' => 'Deliciosos brownies horneados en casa con chocolate y arequipe de primera calidad.',
                    'category' => 'postres',
                    'condition' => 'new',
                    'price' => 3500.00,
                    'image' => json_encode(['https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=600&q=80']),
                    'status' => 'approved'
                ],
                [
                    'user_id' => $user1,
                    'school_id' => 6, // Calasanz
                    'title' => 'Tutorías de Matemáticas & Álgebra',
                    'description' => 'Explicaciones paso a paso de álgebra y resolución de guías para exámenes.',
                    'category' => 'tutorias',
                    'condition' => 'new',
                    'price' => 15000.00,
                    'image' => json_encode(['https://images.unsplash.com/photo-1434030216411-0b793f4b4173?auto=format&fit=crop&w=600&q=80']),
                    'status' => 'approved'
                ],
                [
                    'user_id' => $user1,
                    'school_id' => 1, // Vegas
                    'title' => 'Stickers de Vinilo & Anime',
                    'description' => 'Pack de 3 stickers impermeables para pegar en cuadernos o termos de agua.',
                    'category' => 'arte',
                    'condition' => 'new',
                    'price' => 2000.00,
                    'image' => json_encode(['https://images.unsplash.com/photo-1572375992501-4b0892d50c69?auto=format&fit=crop&w=600&q=80']),
                    'status' => 'approved'
                ]
            ];

            $insP = $db->prepare("INSERT INTO student_products (user_id, school_id, title, description, category, `condition`, price, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($prods as $p) {
                $insP->execute([
                    $p['user_id'], $p['school_id'], $p['title'], $p['description'],
                    $p['category'], $p['condition'], $p['price'], $p['image'], $p['status']
                ]);
                echo "Created product: " . $p['title'] . "\n";
            }
        }
    }

    echo "Seeding completed successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

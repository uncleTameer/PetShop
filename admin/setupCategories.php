<?php
require_once '../php/dbConnect.php';

// Default categories to add
$defaultCategories = [
    ['name' => 'Toys', 'description' => 'Fun toys for pets to play with'],
    ['name' => 'Food', 'description' => 'Pet food and treats'],
    ['name' => 'Accessories', 'description' => 'Collars, leashes, and other accessories'],
    ['name' => 'Health & Care', 'description' => 'Health products and grooming supplies'],
    ['name' => 'Beds & Furniture', 'description' => 'Comfortable beds and furniture for pets'],
    ['name' => 'Training', 'description' => 'Training tools and equipment']
];

$addedCount = 0;
$existingCount = 0;

foreach ($defaultCategories as $category) {
    // Check if category already exists
    $existing = $db->categories->findOne(['name' => $category['name']]);
    
    if (!$existing) {
        $db->categories->insertOne([
            'name' => $category['name'],
            'description' => $category['description'],
            'createdAt' => new MongoDB\BSON\UTCDateTime()
        ]);
        $addedCount++;
        echo "✅ Added category: {$category['name']}\n";
    } else {
        $existingCount++;
        echo "⏭️  Category already exists: {$category['name']}\n";
    }
}

echo "\n📊 Summary:\n";
echo "Added: $addedCount categories\n";
echo "Already existed: $existingCount categories\n";
echo "\n🎉 Category setup complete! You can now manage categories at: /admin/manageCategories.php\n";
?> 
<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Root Categories
        $rootCategories = [
            [
                'name' => 'Health & Medical',
                'icon' => '🏥',
                'description' => 'Healthcare providers and medical services',
                'children' => [
                    ['name' => 'Pharmacies', 'icon' => '💊'],
                    ['name' => 'Hospitals', 'icon' => '🏨'],
                    ['name' => 'Clinics', 'icon' => '🏥'],
                    ['name' => 'Laboratories', 'icon' => '🔬'],
                    ['name' => 'Dental Clinics', 'icon' => '🦷'],
                    ['name' => 'Optical Centers', 'icon' => '👓'],
                ]
            ],
            [
                'name' => 'Food & Dining',
                'icon' => '🍽️',
                'description' => 'Restaurants, cafes, and food services',
                'children' => [
                    ['name' => 'Restaurants', 'icon' => '🍴'],
                    ['name' => 'Bakeries', 'icon' => '🍞'],
                    ['name' => 'Cafés', 'icon' => '☕'],
                    ['name' => 'Fast Food', 'icon' => '🍔'],
                    ['name' => 'Catering Services', 'icon' => '🍱'],
                ]
            ],
            [
                'name' => 'Shopping & Retail',
                'icon' => '🛍️',
                'description' => 'Retail stores and shopping services',
                'children' => [
                    ['name' => 'Supermarkets', 'icon' => '🛒'],
                    ['name' => 'Provision Stores', 'icon' => '📦'],
                    ['name' => 'Clothing Stores', 'icon' => '👗'],
                    ['name' => 'Electronics Stores', 'icon' => '📱'],
                    ['name' => 'Furniture Stores', 'icon' => '🪑'],
                    ['name' => 'Hardware Stores', 'icon' => '🔧'],
                ]
            ],
            [
                'name' => 'Education',
                'icon' => '📚',
                'description' => 'Educational institutions and services',
                'children' => [
                    ['name' => 'Universities', 'icon' => '🎓'],
                    ['name' => 'Colleges', 'icon' => '🏫'],
                    ['name' => 'Primary Schools', 'icon' => '📖'],
                    ['name' => 'Secondary Schools', 'icon' => '📐'],
                    ['name' => 'Vocational Training', 'icon' => '🔨'],
                    ['name' => 'Tutoring Services', 'icon' => '✏️'],
                ]
            ],
            [
                'name' => 'Hospitality & Travel',
                'icon' => '🏨',
                'description' => 'Hotels, guest houses, and travel services',
                'children' => [
                    ['name' => 'Hotels', 'icon' => '🏨'],
                    ['name' => 'Guest Houses', 'icon' => '🏠'],
                    ['name' => 'Resorts', 'icon' => '🌴'],
                    ['name' => 'Travel Agencies', 'icon' => '✈️'],
                ]
            ],
            [
                'name' => 'Financial Services',
                'icon' => '💰',
                'description' => 'Banks, insurance, and financial institutions',
                'children' => [
                    ['name' => 'Banks', 'icon' => '🏦'],
                    ['name' => 'Microfinance', 'icon' => '💳'],
                    ['name' => 'Insurance', 'icon' => '🛡️'],
                    ['name' => 'Money Transfer', 'icon' => '💸'],
                ]
            ],
            [
                'name' => 'Professional Services',
                'icon' => '💼',
                'description' => 'Professional and business services',
                'children' => [
                    ['name' => 'Law Firms', 'icon' => '⚖️'],
                    ['name' => 'Accounting Firms', 'icon' => '📊'],
                    ['name' => 'Consulting', 'icon' => '🤝'],
                    ['name' => 'Real Estate', 'icon' => '🏠'],
                ]
            ],
            [
                'name' => 'Automotive',
                'icon' => '🚗',
                'description' => 'Car services and dealerships',
                'children' => [
                    ['name' => 'Auto Mechanics', 'icon' => '🔧'],
                    ['name' => 'Car Dealerships', 'icon' => '🚘'],
                    ['name' => 'Fuel Stations', 'icon' => '⛽'],
                    ['name' => 'Car Wash', 'icon' => '🧼'],
                ]
            ],
            [
                'name' => 'Beauty & Wellness',
                'icon' => '💇',
                'description' => 'Salons, spas, and wellness services',
                'children' => [
                    ['name' => 'Salons', 'icon' => '💇'],
                    ['name' => 'Barbershops', 'icon' => '✂️'],
                    ['name' => 'Spas', 'icon' => '🧖'],
                    ['name' => 'Fitness Centers', 'icon' => '💪'],
                ]
            ],
            [
                'name' => 'Technology',
                'icon' => '💻',
                'description' => 'Tech services and internet providers',
                'children' => [
                    ['name' => 'Cyber Cafés', 'icon' => '🖥️'],
                    ['name' => 'Internet Services', 'icon' => '🌐'],
                    ['name' => 'Phone Shops', 'icon' => '📱'],
                    ['name' => 'Printing Services', 'icon' => '🖨️'],
                    ['name' => 'Software Development', 'icon' => '⌨️'],
                ]
            ],
            [
                'name' => 'Religious & Community',
                'icon' => '⛪',
                'description' => 'Religious institutions and community organizations',
                'children' => [
                    ['name' => 'Churches', 'icon' => '⛪'],
                    ['name' => 'Mosques', 'icon' => '🕌'],
                    ['name' => 'NGOs', 'icon' => '🤲'],
                    ['name' => 'Community Centers', 'icon' => '🏘️'],
                ]
            ],
            [
                'name' => 'Government & Public',
                'icon' => '🏛️',
                'description' => 'Government institutions and public services',
                'children' => [
                    ['name' => 'Government Offices', 'icon' => '🏛️'],
                    ['name' => 'Public Services', 'icon' => '📋'],
                    ['name' => 'Embassies', 'icon' => '🕊️'],
                ]
            ],
            [
                'name' => 'Entertainment & Events',
                'icon' => '🎭',
                'description' => 'Entertainment venues and event services',
                'children' => [
                    ['name' => 'Event Centers', 'icon' => '🎪'],
                    ['name' => 'Cinemas', 'icon' => '🎬'],
                    ['name' => 'Nightclubs', 'icon' => '💃'],
                ]
            ],
        ];

        foreach ($rootCategories as $rootData) {
            $root = Category::create([
                'name' => $rootData['name'],
                'icon' => $rootData['icon'],
                'description' => $rootData['description'] ?? null,
                'is_active' => true,
                'sort_order' => 0,
            ]);

            if (isset($rootData['children'])) {
                foreach ($rootData['children'] as $index => $childData) {
                    Category::create([
                        'parent_id' => $root->id,
                        'name' => $childData['name'],
                        'icon' => $childData['icon'],
                        'is_active' => true,
                        'sort_order' => $index,
                    ]);
                }
            }
        }
    }
}
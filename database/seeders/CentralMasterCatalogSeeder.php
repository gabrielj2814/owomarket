<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CentralBrand;
use App\Models\CentralCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class CentralMasterCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCentralBrands();
        $this->seedCentralCategories();
    }

    private function seedCentralBrands(): void
    {
        $brands = [
            ['name' => 'Apple', 'slug' => 'apple', 'logo' => 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=120&h=120&fit=crop', 'description' => 'Innovación y tecnología de punta en dispositivos móviles y computadoras.'],
            ['name' => 'Samsung', 'slug' => 'samsung', 'logo' => 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=120&h=120&fit=crop', 'description' => 'Líder global en electrónica de consumo y pantallas inteligentes.'],
            ['name' => 'Sony', 'slug' => 'sony', 'logo' => 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=120&h=120&fit=crop', 'description' => 'Audio de alta fidelidad, cámaras profesionales y entretenimiento.'],
            ['name' => 'Xiaomi', 'slug' => 'xiaomi', 'logo' => 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=120&h=120&fit=crop', 'description' => 'Smartphones potentes y ecosistema para el hogar inteligente.'],
            ['name' => 'Nike', 'slug' => 'nike', 'logo' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=120&h=120&fit=crop', 'description' => 'Calzado, indumentaria y equipamiento deportivo de máximo rendimiento.'],
            ['name' => 'Adidas', 'slug' => 'adidas', 'logo' => 'https://images.unsplash.com/photo-1518002171953-a080ee817e1f?w=120&h=120&fit=crop', 'description' => 'Pasión deportiva y moda urbana icónica.'],
            ['name' => 'Puma', 'slug' => 'puma', 'logo' => 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=120&h=120&fit=crop', 'description' => 'Velocidad, estilo y rendimiento en calzado e indumentaria.'],
            ['name' => 'Logitech', 'slug' => 'logitech', 'logo' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=120&h=120&fit=crop', 'description' => 'Periféricos premium para productividad y videojuegos.'],
            ['name' => 'Asus', 'slug' => 'asus', 'logo' => 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=120&h=120&fit=crop', 'description' => 'Laptops para gaming (ROG) y computación de alto rendimiento.'],
            ['name' => 'Lenovo', 'slug' => 'lenovo', 'logo' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=120&h=120&fit=crop', 'description' => 'Equipos ThinkPad y portátiles para empresas y profesionales.'],
            ['name' => 'Casio', 'slug' => 'casio', 'logo' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=120&h=120&fit=crop', 'description' => 'Relojes resistentes G-Shock e instrumentos de precisión.'],
            ['name' => 'Nintendo', 'slug' => 'nintendo', 'logo' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=120&h=120&fit=crop', 'description' => 'Consolas híbridas Switch y videojuegos familiares.'],
        ];

        foreach ($brands as $index => $brandData) {
            CentralBrand::updateOrCreate(
                ['slug' => $brandData['slug']],
                [
                    'name' => $brandData['name'],
                    'slug' => $brandData['slug'],
                    'description' => $brandData['description'],
                    'logo' => $brandData['logo'],
                    'is_active' => true,
                    'position' => $index + 1,
                ]
            );
        }
    }

    private function seedCentralCategories(): void
    {
        $categoriesTree = [
            [
                'name' => 'Tecnología & Electrónica',
                'slug' => 'tecnologia-electronica',
                'icon' => 'LuCpu',
                'image' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=600&h=400&fit=crop',
                'description' => 'Lo último en tecnología, computación y dispositivos inteligentes.',
                'children' => [
                    ['name' => 'Smartphones & Telefonía', 'slug' => 'smartphones-telefonia', 'icon' => 'LuSmartphone', 'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600&h=400&fit=crop'],
                    ['name' => 'Laptops & Computación', 'slug' => 'laptops-computacion', 'icon' => 'LuLaptop', 'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600&h=400&fit=crop'],
                    ['name' => 'Audio & Sonido', 'slug' => 'audio-sonido', 'icon' => 'LuHeadphones', 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&h=400&fit=crop'],
                    ['name' => 'Gaming & Consolas', 'slug' => 'gaming-consolas', 'icon' => 'LuGamepad', 'image' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=600&h=400&fit=crop'],
                    ['name' => 'Smartwatches & Wearables', 'slug' => 'smartwatches-wearables', 'icon' => 'LuWatch', 'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&h=400&fit=crop'],
                ],
            ],
            [
                'name' => 'Moda & Calzado',
                'slug' => 'moda-calzado',
                'icon' => 'LuShirt',
                'image' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=600&h=400&fit=crop',
                'description' => 'Tendencias en vestuario, zapatillas y accesorios de moda.',
                'children' => [
                    ['name' => 'Ropa Hombre', 'slug' => 'ropa-hombre', 'icon' => 'LuShirt', 'image' => 'https://images.unsplash.com/photo-1617137984095-74e4e5e3613f?w=600&h=400&fit=crop'],
                    ['name' => 'Ropa Mujer', 'slug' => 'ropa-mujer', 'icon' => 'LuSparkles', 'image' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600&h=400&fit=crop'],
                    ['name' => 'Calzado Deportivo', 'slug' => 'calzado-deportivo', 'icon' => 'LuFootprints', 'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&h=400&fit=crop'],
                    ['name' => 'Accesorios & Bolsos', 'slug' => 'accesorios-bolsos', 'icon' => 'LuGlasses', 'image' => 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=600&h=400&fit=crop'],
                ],
            ],
            [
                'name' => 'Hogar & Electrodomésticos',
                'slug' => 'hogar-electrodomesticos',
                'icon' => 'LuHome',
                'image' => 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?w=600&h=400&fit=crop',
                'description' => 'Muebles, decoración, cocina y electrodomésticos para tu hogar.',
                'children' => [
                    ['name' => 'Cocina & Menaje', 'slug' => 'cocina-menaje', 'icon' => 'LuUtensils', 'image' => 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?w=600&h=400&fit=crop'],
                    ['name' => 'Muebles & Decoración', 'slug' => 'muebles-decoracion', 'icon' => 'LuArmchair', 'image' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=600&h=400&fit=crop'],
                    ['name' => 'Iluminación & Climatización', 'slug' => 'iluminacion-climatizacion', 'icon' => 'LuLightbulb', 'image' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=600&h=400&fit=crop'],
                ],
            ],
            [
                'name' => 'Deportes & Fitness',
                'slug' => 'deportes-fitness',
                'icon' => 'LuTrophy',
                'image' => 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=600&h=400&fit=crop',
                'description' => 'Equipamiento para entrenamiento, gimnasio y aire libre.',
                'children' => [
                    ['name' => 'Gimnasio & Musculación', 'slug' => 'gimnasio-musculacion', 'icon' => 'LuDumbbell', 'image' => 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=600&h=400&fit=crop'],
                    ['name' => 'Ciclismo & Movilidad', 'slug' => 'ciclismo-movilidad', 'icon' => 'LuBike', 'image' => 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?w=600&h=400&fit=crop'],
                ],
            ],
        ];

        $rootPosition = 1;
        foreach ($categoriesTree as $rootCatData) {
            $rootCat = CentralCategory::updateOrCreate(
                ['slug' => $rootCatData['slug']],
                [
                    'name' => $rootCatData['name'],
                    'slug' => $rootCatData['slug'],
                    'icon' => $rootCatData['icon'],
                    'image' => $rootCatData['image'],
                    'description' => $rootCatData['description'] ?? null,
                    'parent_id' => null,
                    'is_active' => true,
                    'position' => $rootPosition++,
                ]
            );

            if (!empty($rootCatData['children'])) {
                $childPos = 1;
                foreach ($rootCatData['children'] as $childData) {
                    CentralCategory::updateOrCreate(
                        ['slug' => $childData['slug']],
                        [
                            'name' => $childData['name'],
                            'slug' => $childData['slug'],
                            'icon' => $childData['icon'],
                            'image' => $childData['image'],
                            'parent_id' => $rootCat->id,
                            'is_active' => true,
                            'position' => $childPos++,
                        ]
                    );
                }
            }
        }
    }
}

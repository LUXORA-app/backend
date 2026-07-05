<?php

namespace Database\Seeders;

use App\Models\Landmark;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Clear all users and related data (clean slate)
        \Laravel\Sanctum\PersonalAccessToken::query()->delete();
        \App\Models\Favorite::query()->delete();
        \App\Models\ChatMessage::query()->delete();
        \App\Models\Translation::query()->delete();
        \App\Models\Album::query()->delete();
        User::query()->delete();

        $landmarks = [
            [
                'name' => 'Luxor Temple',
                'description' => 'Luxor Temple is a large Ancient Egyptian temple complex located on the east bank of the Nile River in the city today known as Luxor. It was constructed approximately 1400 BCE by Amenhotep III and later expanded by Ramesses II. The temple was dedicated to the rejuvenation of kingship and was the focal point of the annual Opet festival.',
                'location' => 'East Bank, Luxor',
                'latitude' => 25.6995,
                'longitude' => 32.6389,
                'image_url' => 'https://images.unsplash.com/photo-1568603417743-488f5724bc98?q=80&w=600',
            ],
            [
                'name' => 'Karnak Temple',
                'description' => 'The Karnak Temple Complex comprises a vast mix of decayed temples, pylons, chapels, and other buildings near Luxor. It is the largest ancient religious site in the world, developed over 2,000 years by various pharaohs. The Great Hypostyle Hall is one of its most impressive features.',
                'location' => 'East Bank, Luxor',
                'latitude' => 25.7188,
                'longitude' => 32.6573,
                'image_url' => 'https://images.unsplash.com/photo-1548625361-b84784a9c379?q=80&w=600',
            ],
            [
                'name' => 'Valley of the Kings',
                'description' => 'The Valley of the Kings is a valley in Egypt where, for nearly 500 years, rock-cut tombs were excavated for the pharaohs and powerful nobles of the New Kingdom. It contains 63 tombs including that of Tutankhamun.',
                'location' => 'West Bank, Luxor',
                'latitude' => 25.7402,
                'longitude' => 32.6014,
                'image_url' => 'https://images.unsplash.com/photo-1596525737227-3df47833075d?q=80&w=600',
            ],
            [
                'name' => 'Hatshepsut Temple',
                'description' => 'The Mortuary Temple of Hatshepsut is a mortuary temple built for the Eighteenth Dynasty pharaoh Hatshepsut. Located beneath the cliffs at Deir el-Bahari, it is one of the most architecturally unique temples in Egypt.',
                'location' => 'Deir el-Bahari, Luxor',
                'latitude' => 25.7382,
                'longitude' => 32.6066,
                'image_url' => 'https://images.unsplash.com/photo-1553913861-c0fddf2619ee?q=80&w=600',
            ],
            [
                'name' => 'Colossi of Memnon',
                'description' => 'The Colossi of Memnon are two massive stone statues of Pharaoh Amenhotep III, which have stood in the Theban Necropolis since 1350 BCE. Each statue is about 18 meters tall and weighs 720 tonnes.',
                'location' => 'West Bank, Luxor',
                'latitude' => 25.7206,
                'longitude' => 32.6105,
                'image_url' => 'https://images.unsplash.com/photo-1539650116574-8efeb43e2750?q=80&w=600',
            ],
            [
                'name' => 'Great Pyramids of Giza',
                'description' => 'The Great Pyramid of Giza is the oldest and largest of the three pyramids in the Giza pyramid complex. It is the oldest of the Seven Wonders of the Ancient World and the only one to remain largely intact.',
                'location' => 'Giza, Cairo',
                'latitude' => 29.9792,
                'longitude' => 31.1342,
                'image_url' => 'https://images.unsplash.com/photo-1572252009286-268acec5ca0a?q=80&w=600',
            ],
            [
                'name' => 'The Great Sphinx',
                'description' => 'The Great Sphinx of Giza is a limestone statue of a mythical creature with the head of a human and the body of a lion. It was built during the reign of Pharaoh Khafre (c. 2558–2532 BC).',
                'location' => 'Giza, Cairo',
                'latitude' => 29.9753,
                'longitude' => 31.1376,
                'image_url' => 'https://images.unsplash.com/photo-1594650888073-c6c4917de23d?q=80&w=600',
            ],
            [
                'name' => 'Egyptian Museum',
                'description' => 'The Museum of Egyptian Antiquities in Cairo houses a vast collection of ancient Egyptian antiquities, including the treasures of Tutankhamun and royal mummies. It contains over 120,000 items.',
                'location' => 'Tahrir, Cairo',
                'latitude' => 30.0478,
                'longitude' => 31.2336,
                'image_url' => 'https://images.unsplash.com/photo-1615473967657-9dc21773a1d0?q=80&w=600',
            ],
        ];

        foreach ($landmarks as $landmark) {
            Landmark::updateOrCreate(
                ['name' => $landmark['name']],
                $landmark
            );
        }

        // Create one Admin user (clean start)
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@luxora.com',
            'password' => bcrypt('password'),
        ]);
        $admin->forceFill(['role' => 'admin'])->save();
    }
}

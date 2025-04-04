<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackageFeature;
use Illuminate\Database\Seeder;

class PackageFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $featuresByPackage = [
            '5 star package'  => [
                ['name' => 'Photographer', 'status' => 'locked'],
                ['name' => 'Gift Bags', 'status' => 'accessible'],
                ['name' => 'Balloon Arch', 'status' => 'locked'],
            ],
            '5+ star package' => [
                ['name' => 'Photographer', 'status' => 'accessible'],
                ['name' => 'Gift Bags', 'status' => 'accessible'],
                ['name' => 'Balloon Arch', 'status' => 'accessible'],
            ],
        ];

        foreach ($featuresByPackage as $packageName => $features) {
            $package = Package::where('name', $packageName)->first();

            if ($package) {
                foreach ($features as $feature) {
                    PackageFeature::create([
                        'package_id' => $package->id,
                        'name'       => $feature['name'],
                        'status'     => $feature['status'],
                    ]);
                }
            }
        }
    }
}

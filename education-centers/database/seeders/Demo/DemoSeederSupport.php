<?php

namespace Database\Seeders\Demo;

use App\Models\Center;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait DemoSeederSupport
{
    protected function centerAdminId(Center $center): int
    {
        $id = User::query()
            ->where('center_id', $center->id)
            ->where('role', User::ROLE_CENTER_ADMIN)
            ->orderBy('id')
            ->value('id');

        if ($id) {
            return (int) $id;
        }

        $id = User::query()
            ->where('role', User::ROLE_SUPER_ADMIN)
            ->orderBy('id')
            ->value('id');

        if ($id) {
            return (int) $id;
        }

        return $this->ensureDemoUser(
            sprintf('admin.%s@education-centers.test', strtolower($center->code)),
            'Administrateur '.DemoFixtures::centerLabel($center),
            User::ROLE_CENTER_ADMIN,
            $center->id,
        );
    }

    protected function ensureDemoUser(string $email, string $name, string $role, ?int $centerId): int
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'role' => $role,
                'center_id' => $centerId,
                'status' => User::STATUS_ACTIVE,
                'locale' => 'fr',
                'password' => Hash::make($this->demoPassword()),
            ],
        );

        return (int) $user->id;
    }

    protected function demoPassword(): string
    {
        return (string) env(DemoFixtures::DEMO_PASSWORD_ENV, 'DemoEducation2026!');
    }

    protected function randomPasswordHash(): string
    {
        return Hash::make(Str::random(40));
    }
}

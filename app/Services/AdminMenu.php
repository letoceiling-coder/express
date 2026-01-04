<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class AdminMenu
{
    /**
     * Получить меню для пользователя с фильтрацией по ролям
     *
     * @param User|null $user
     * @return Collection
     */
    public function getMenu(?User $user = null): Collection
    {
        $menu = collect([
            [
                'title' => 'Главная',
                'route' => 'admin.dashboard',
                'icon' => 'home',
                'roles' => ['admin', 'manager'],
            ],
            // Каталог (группа)
            [
                'title' => 'Каталог',
                'icon' => 'shopping-cart',
                'roles' => ['admin', 'manager'],
                'children' => [
                    [
                        'title' => 'Категории',
                        'route' => 'admin.categories',
                        'icon' => 'folder',
                        'roles' => ['admin', 'manager'],
                    ],
                    [
                        'title' => 'Товары',
                        'route' => 'admin.products',
                        'icon' => 'database',
                        'roles' => ['admin', 'manager'],
                    ],
                ],
            ],
            // Заказы (группа)
            [
                'title' => 'Заказы',
                'icon' => 'shopping-cart',
                'roles' => ['admin', 'manager'],
                'children' => [
                    [
                        'title' => 'Заказы',
                        'route' => 'admin.orders',
                        'icon' => 'shopping-cart',
                        'roles' => ['admin', 'manager'],
                    ],
                    [
                        'title' => 'Доставки',
                        'route' => 'admin.deliveries',
                        'icon' => 'shopping-cart',
                        'roles' => ['admin', 'manager'],
                    ],
                    [
                        'title' => 'Платежи',
                        'route' => 'admin.payments',
                        'icon' => 'credit-card',
                        'roles' => ['admin', 'manager'],
                    ],
                ],
            ],
            // Обратная связь (группа)
            [
                'title' => 'Обратная связь',
                'icon' => 'users',
                'roles' => ['admin', 'manager'],
                'children' => [
                    [
                        'title' => 'Возвраты',
                        'route' => 'admin.returns',
                        'icon' => 'shopping-cart',
                        'roles' => ['admin', 'manager'],
                    ],
                    [
                        'title' => 'Претензии',
                        'route' => 'admin.complaints',
                        'icon' => 'award',
                        'roles' => ['admin', 'manager'],
                    ],
                    [
                        'title' => 'Отзывы',
                        'route' => 'admin.reviews',
                        'icon' => 'award',
                        'roles' => ['admin', 'manager'],
                    ],
                ],
            ],
            [
                'title' => 'Медиа',
                'route' => 'admin.media',
                'icon' => 'image',
                'roles' => ['admin', 'manager'],
            ],
            [
                'title' => 'Уведомления',
                'route' => 'admin.notifications',
                'icon' => 'bell',
                'roles' => ['admin', 'manager', 'user'],
            ],
            [
                'title' => 'Пользователи',
                'route' => 'admin.users',
                'icon' => 'users',
                'roles' => ['admin'],
            ],
            [
                'title' => 'Роли',
                'route' => 'admin.roles',
                'icon' => 'shield',
                'roles' => ['admin'],
            ],
            [
                'title' => 'Пользователи бота',
                'route' => 'admin.telegram-users',
                'icon' => 'users',
                'roles' => ['admin', 'manager'],
            ],
            [
                'title' => 'Рассылки',
                'route' => 'admin.broadcasts',
                'icon' => 'send',
                'roles' => ['admin', 'manager'],
            ],
            [
                'title' => 'Боты',
                'route' => 'admin.bots',
                'icon' => 'bot',
                'roles' => ['admin'],
            ],
            // Настройки (группа)
            [
                'title' => 'Настройки',
                'icon' => 'settings',
                'roles' => ['admin'],
                'children' => [
                    [
                        'title' => 'Платежи (ЮКасса)',
                        'route' => 'admin.settings.payments.yookassa',
                        'icon' => 'credit-card',
                        'roles' => ['admin'],
                    ],
                    [
                        'title' => 'Общие',
                        'route' => 'admin.settings',
                        'icon' => 'settings',
                        'roles' => ['admin'],
                    ],
                ],
            ],
            [
                'title' => 'Подписка',
                'route' => 'admin.subscription',
                'icon' => 'credit-card',
                'roles' => ['admin', 'manager'],
            ],
            [
                'title' => 'Поддержка',
                'route' => 'admin.support',
                'icon' => 'chat',
                'roles' => ['admin', 'manager'],
            ],
            [
                'title' => 'Документация',
                'route' => 'admin.documentation',
                'icon' => 'book',
                'roles' => ['admin', 'manager', 'user'],
            ],
        ]);

        if (!$user) {
            return collect([]);
        }

        // Получаем роли пользователя
        $userRoles = $user->roles->pluck('slug')->toArray();

        // Фильтруем меню по ролям
        return $menu->map(function ($item) use ($userRoles) {
            // Проверяем доступ к родительскому элементу
            if (!empty($item['roles']) && !$this->hasAccess($userRoles, $item['roles'])) {
                return null;
            }

            // Фильтруем дочерние элементы
            if (isset($item['children'])) {
                $item['children'] = collect($item['children'])->filter(function ($child) use ($userRoles) {
                    return empty($child['roles']) || $this->hasAccess($userRoles, $child['roles']);
                })->values()->toArray();

                // Если нет доступных дочерних элементов, скрываем родительский
                if (empty($item['children'])) {
                    return null;
                }
            }

            return $item;
        })->filter()->values();
    }

    /**
     * Проверить доступ пользователя к элементу меню
     *
     * @param array $userRoles
     * @param array $requiredRoles
     * @return bool
     */
    protected function hasAccess(array $userRoles, array $requiredRoles): bool
    {
        return !empty(array_intersect($userRoles, $requiredRoles));
    }

    /**
     * Получить меню в формате JSON для API
     *
     * @param User|null $user
     * @return array
     */
    public function getMenuJson(?User $user = null): array
    {
        return $this->getMenu($user)->toArray();
    }
}

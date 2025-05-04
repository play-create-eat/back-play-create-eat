<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;

class AdminResourceBase extends Resource
{
    /**
     * Check if the resource can be created
     */
    public static function canCreate(): bool
    {
        return static::checkPermission('create-' . static::getResourcePermissionName());
    }

    /**
     * Define methods to check for specific permissions
     */
    protected static function checkPermission(string $permission): bool
    {
        $admin = auth('admin')->user();

        return $admin && ($admin->hasPermissionTo($permission, 'admin') || $admin->hasRole('super-admin'));
    }

    /**
     * Get the permission name based on the resource name
     */
    protected static function getResourcePermissionName(): string
    {
        $resourceName = static::getModelLabel();
        return strtolower($resourceName);
    }

    /**
     * Check if the resource can be edited
     */
    public static function canEdit($record): bool
    {
        return static::checkPermission('edit-' . static::getResourcePermissionName());
    }

    /**
     * Check if the resource can be deleted
     */
    public static function canDelete($record): bool
    {
        return static::checkPermission('delete-' . static::getResourcePermissionName());
    }

    /**
     * Define common functionality for admin resources
     */
    public static function getNavigationGroup(): ?string
    {
        return 'Admin Management';
    }
}

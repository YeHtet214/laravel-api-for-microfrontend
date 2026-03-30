# Deployment Seeding Guide for Laravel Cloud

## Overview

This guide explains how demo data is seeded at build time on Laravel Cloud so that users can access it at runtime for demo purposes.

## How It Works

### Build-Time Seeding

When you deploy to Laravel Cloud, the `setup` script in [`composer.json`](composer.json:40) runs automatically:

```json
"setup": [
    "composer install",
    "@php -r \"file_exists('.env') || copy('.env.example', '.env');\"",
    "@php artisan key:generate",
    "@php artisan migrate --force",
    "@php artisan db:seed --force",
    "npm install",
    "npm run build"
]
```

The key addition is `@php artisan db:seed --force` which:
1. Runs after migrations complete
2. Uses `--force` flag to run in production
3. Executes the [`DatabaseSeeder`](database/seeders/DatabaseSeeder.php:10)

### What Gets Seeded

The [`DatabaseSeeder`](database/seeders/DatabaseSeeder.php:10) runs the following seeders:

1. **Admin User & SSO Client** (always runs)
   - Admin user: `admin@test.com` / `password`
   - SSO client for microfrontend authentication

2. **RBACSeeder** (via [`RBACSeeder`](database/seeders/RBACSeeder.php:10))
   - Creates 20+ permissions for the system
   - Creates 4 roles: Admin, Product Manager, Inventory Manager, Staff
   - Assigns permissions to roles
   - Assigns Admin role to the first user

3. **ProductSeeder** (via [`ProductSeeder`](database/seeders/ProductSeeder.php:10))
   - Creates 3 categories: Electronics, Apparel, Home & Kitchen
   - Creates products with variants:
     - Smartphone X (simple product)
     - Pro Laptop 14 (with RAM/Storage variants)
     - Classic Cotton T-Shirt (with Color/Size variants)

## Idempotent Design

All seeders use `updateOrCreate` method, making them **idempotent**:
- Safe to run multiple times
- Won't duplicate data
- Updates existing records if they exist
- Creates new records if they don't exist

This means:
- First deployment: Creates all demo data
- Subsequent deployments: Updates existing data, no duplicates
- Safe for CI/CD pipelines

## Deployment on Laravel Cloud

### Automatic Seeding

When you push to Laravel Cloud:

1. **Build Phase**
   - Composer dependencies installed
   - Environment file created
   - Application key generated
   - Migrations run
   - **Seeders run** ← Demo data is created here
   - NPM dependencies installed
   - Frontend assets built

2. **Runtime Phase**
   - Application starts
   - Demo data is already in the database
   - Users can immediately access demo content

### Manual Seeding (If Needed)

If you need to manually seed after deployment:

```bash
# Via Laravel Cloud CLI
laravel-cloud ssh
php artisan db:seed --force

# Or via Laravel Cloud dashboard
# Go to your project → SSH → Run the command
```

## Demo Data Available

After seeding, users can access:

### Authentication
- **Admin Login**: `admin@test.com` / `password`
- Full admin access to all features

### Products API
```bash
# Get all products
GET /api/products

# Get all categories
GET /api/categories

# Get specific product
GET /api/products/smartphone-x
```

### Roles & Permissions
- Admin role with all permissions
- Product Manager role
- Inventory Manager role
- Staff role

## Testing After Deployment

### Via API

```bash
# Test products endpoint
curl https://your-app.laravel.cloud/api/products

# Test categories endpoint
curl https://your-app.laravel.cloud/api/categories

# Test authentication
curl -X POST https://your-app.laravel.cloud/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"password"}'
```

### Via Laravel Cloud SSH

```bash
# SSH into your Laravel Cloud instance
laravel-cloud ssh

# Check if data exists
php artisan tinker --execute="echo App\Models\Product::count();"

# Check users
php artisan tinker --execute="echo App\Models\User::count();"

# Check roles
php artisan tinker --execute="echo App\Models\Role::count();"
```

## Troubleshooting

### Seeding Fails During Deployment

**Symptoms**: Deployment fails at seeding step

**Solutions**:
1. Check Laravel Cloud logs for error details
2. Verify database connection is working
3. Ensure migrations completed successfully
4. Check if seeders have any syntax errors

```bash
# Test seeders locally first
php artisan db:seed --force
```

### Data Not Appearing After Deployment

**Symptoms**: API returns empty results

**Solutions**:
1. SSH into Laravel Cloud and check database
2. Manually run seeder
3. Check if migrations ran successfully

```bash
# Check migration status
php artisan migrate:status

# Run seeder manually
php artisan db:seed --force
```

### Duplicate Data Issues

**Symptoms**: Multiple copies of same data

**Solutions**:
- This shouldn't happen due to `updateOrCreate` usage
- If it does, check if seeders were modified
- Verify `updateOrCreate` is used consistently

## Best Practices

1. **Always Test Locally First**
   ```bash
   php artisan migrate:fresh --seed
   ```

2. **Use Idempotent Seeders**
   - All seeders in this project use `updateOrCreate`
   - Safe to run multiple times

3. **Monitor First Deployment**
   - Watch Laravel Cloud logs during first deployment
   - Verify seeding completes successfully

4. **Keep Seeders Updated**
   - Update seeders when adding new features
   - Ensure demo data reflects current application state

5. **Document Demo Data**
   - Keep this guide updated
   - Document any changes to demo data structure

## Alternative: Environment-Specific Seeding

If you need different seeding for different environments:

### Option 1: Conditional Seeding in DatabaseSeeder

```php
public function run(): void
{
    // Always create admin
    User::updateOrCreate(['email' => 'admin@test.com'], [...]);
    
    // Only seed demo data in production
    if (app()->environment('production')) {
        $this->call([
            RBACSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
```

### Option 2: Use DeploymentSeeder

The [`DeploymentSeeder`](database/seeders/DeploymentSeeder.php:10) can be used for production-specific seeding:

```bash
# In composer.json, use DeploymentSeeder instead
"@php artisan db:seed --class=DeploymentSeeder --force"
```

## Summary

- **Demo data is seeded at build time** via the `setup` script
- **All seeders are idempotent** - safe to run multiple times
- **Users can access demo data immediately** after deployment
- **No manual intervention required** - fully automated
- **Laravel Cloud handles everything** during deployment

The seeding process is designed to be:
- ✅ Automatic
- ✅ Safe
- ✅ Idempotent
- ✅ Production-ready
- ✅ User-friendly

Users will have immediate access to demo data for testing and demonstration purposes.

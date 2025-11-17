# Building Teardown Feature - Setup Instructions

## Critical Setup Steps

The building teardown feature requires a database migration to function correctly. **Without running the migration, all teardown operations will behave as construction operations** (increasing building levels instead of decreasing them).

### 1. Run the Database Migration

```bash
php artisan migrate
```

This adds the `teardown` column to the `building_queues` table.

### 2. Verify the Migration

Check that the migration was successful:

```bash
php artisan migrate:status
```

You should see:
```
2025_11_08_150000_add_teardown_to_building_queues_table.php ... Ran
```

### 3. Clear Any Cached Data (Optional)

```bash
php artisan cache:clear
php artisan config:clear
```

## Troubleshooting

### Issue: Teardown increases building level instead of decreasing it

**Cause**: The database migration was not run, so the `teardown` column doesn't exist.

**Solution**: Run `php artisan migrate` immediately.

### Issue: Teardown completes too quickly

**Cause**: Same as above - without the teardown column, operations are treated as builds but with incorrect logic.

**Solution**: Run `php artisan migrate` and delete any existing invalid queue entries.

### Issue: Multiple teardowns don't queue properly

**Cause**: Validation logic issue (fixed in latest commit).

**Solution**: Pull latest changes and run migrations.

## Feature Overview

Once properly set up, the teardown feature provides:

- **Cost Calculation**: Teardown cost = Build cost of current level
- **Ion Technology Bonus**: 4% cost reduction per Ion Technology level (max 100% at level 25)
- **Time Calculation**: Uses same formula as construction but with teardown costs
- **Restrictions**:
  - Cannot tear down Terraformer
  - Cannot tear down Lunar Base
  - Cannot tear down Shipyard while building ships/defense
  - Cannot tear down Research Lab while researching
  - Cannot tear down below level 0

## Testing

After setup, you can run the test suite to verify everything works:

```bash
# Run all teardown tests
php artisan test --filter BuildingTeardownTest

# Run a specific test
php artisan test --filter testTeardownMetalMine
```

All 17 tests should pass if the feature is set up correctly.

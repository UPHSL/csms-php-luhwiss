# Preliminary Examination Developer Checkpoint

## Developer Information

- **Name:** Raxell Constantino
- **GitHub Username:** luhwiss
- **Primary Technology Stack:** PHP / Laravel
- **T03 Branch:** feature/t03-resident-persistence

---

## My T03 Implementation

Resident data is stored in a SQLite database using a `residents` table created through a Laravel migration. The component responsible for persistence is `ResidentRepository`, located in `app/Repositories/ResidentRepository.php`. When a Resident is saved, the repository calls `$resident->save()` on the Eloquent model, which inserts a new row into the `residents` table. The identifier is automatically assigned by the database as an auto-incrementing integer primary key — it is not hard-coded. To retrieve a Resident, the repository calls `Resident::find($id)`, which queries the database for the row matching the given identifier. If no matching record exists, `Resident::find()` returns `null`, which is the safe not-found result used throughout the implementation.

---

## Files I Changed

**File:** `database/migrations/2026_08_26_111621_create_residents_table.php`
**Purpose:** Defines the `residents` database table schema with all required columns: `id`, `first_name`, `last_name`, `address`, `contact_number`, `email`, `status`, and timestamps.

**File:** `app/Repositories/ResidentRepository.php`
**Purpose:** Responsible for Resident persistence. Provides `save()` to store a Resident and `findById()` to retrieve a Resident by its identifier.

**File:** `tests/Feature/ResidentRepositoryTest.php`
**Purpose:** Contains all 8 automated tests verifying that Resident persistence works correctly, including storage, identifier assignment, retrieval, data preservation, not-found handling, and multi-instance availability.

---

## Problem I Encountered

**Problem or error:** When first running the T03 tests, the `residents` table did not exist during the test run, causing a database error.

**Cause:** The `RefreshDatabase` trait was not yet added to the test class, so the test database was not being migrated before each test.

**How I resolved it:** Added `use RefreshDatabase;` to `ResidentRepositoryTest`, which causes Laravel to run all migrations fresh before each test and roll them back after, ensuring a clean database state for every test.

---

## My Student-Designed Test

**Test name:** `test_contact_number_leading_zero_is_preserved_after_persistence`

**What it verifies:** After a Resident is saved and retrieved from the database, the contact number still begins with `0` and matches the original value `09171234567` exactly.

**Why I chose this scenario:** The T03 guide specifically warns that the leading zero of a contact number must not be lost during persistence. Storing it as a numeric type would silently drop the leading zero. This test confirms that the `contact_number` column is stored as a string and the value is retrieved intact.

---

## Tools and References Used

- T03 Course Guide
- Laravel Documentation (Eloquent ORM, Migrations, Testing)
- Amazon Q (AI coding assistant — assisted with generating migration, repository, and test file structure)


# Preliminary Examination Developer Checkpoint

## Developer Information

- **Name:** Raxell Constantino
- **GitHub Username:** luhwiss
- **Primary Technology Stack:** PHP with Laravel
- **T03 Branch:** feature/t03-resident-persistence

---

## My T03 Implementation

Resident data is stored in a SQLite database file located at `database/database.sqlite`. The class responsible for persistence is `ResidentRepository`, located in `app/Repositories/ResidentRepository.php`. When `save()` is called, the repository calls `$resident->save()` on the Eloquent model, which inserts a new row into the `residents` table in SQLite. The Resident ID is automatically assigned by the database as an auto-incrementing integer primary key — it is not hard-coded in PHP. To retrieve a Resident, `findById()` calls `Resident::find($id)`, which queries the database for the row matching the given identifier and returns the Eloquent model instance. If no matching record exists, `Resident::find()` returns `null`, which is the safe not-found result used throughout the implementation.

---

## My Persistence Design Decision

**What I decided:** I chose to include Laravel timestamps (`created_at` and `updated_at`) in the migration rather than disabling them on the model.

**Why I implemented it this way:** Laravel Eloquent automatically manages `created_at` and `updated_at` by default. Including them in the migration keeps the model behavior consistent with Laravel conventions without requiring any extra configuration. Removing timestamps would require adding `public $timestamps = false` to the model, which is an extra change that is not necessary for T03.

**Alternative considered:** Disabling timestamps by setting `$timestamps = false` on the Resident model and omitting the timestamp columns from the migration. I chose not to do this because it would require modifying the T01 model unnecessarily.

---

## My Migration Design

**Migration file:** `database/migrations/2026_08_26_111621_create_residents_table.php`

**Primary key design:** `$table->id()` — uses Laravel's auto-incrementing unsigned big integer primary key convention.

**Contact number column type:** `$table->string('contact_number')` — stored as a string to preserve the leading zero (e.g. `09171234567` must not become `9171234567`).

**Status column type:** `$table->string('status')->default('Active')` — stored as a string with a database-level default of `Active`, consistent with the T01 model default.

**Timestamp decision:** Included `$table->timestamps()` to keep the model consistent with Laravel's default Eloquent behavior. No changes to the Resident model were needed.

---

## Files I Changed

**File:** `database/migrations/2026_08_26_111621_create_residents_table.php`
**Purpose:** Defines the `residents` database table schema with all required columns and types.

**File:** `app/Repositories/ResidentRepository.php`
**Purpose:** Responsible for Resident persistence. Provides `save()` to store a Resident and `findById()` to retrieve a Resident by its identifier.

**File:** `tests/Feature/ResidentRepositoryTest.php`
**Purpose:** Contains all 8 automated tests verifying Resident persistence behavior.

**File:** `.gitignore`
**Purpose:** Added `*.sqlite`, `*.sqlite3`, and `*.db` patterns to ensure the SQLite database file is never committed to Git.

---

## Laravel Persistence Behavior I Can Explain

**Behavior selected:** `save()`

When `$resident->save()` is called on an Eloquent model that has not yet been persisted, Laravel checks whether the model has an existing primary key. If the `id` is null, Eloquent performs a SQL `INSERT` statement, inserting all fillable attributes into the `residents` table. After the insert, the database returns the generated auto-increment ID, and Eloquent automatically sets `$resident->id` to that value. This is why the Resident has a usable identifier after `save()` is called, without any manual ID assignment in PHP.

---

## Problem I Encountered

**Problem or error:** When first running the T03 tests, the `residents` table did not exist during the test run, causing a database error.

**Cause:** The `RefreshDatabase` trait was not yet added to the test class, so the test database was not being migrated before each test.

**How I resolved it:** Added `use RefreshDatabase;` to `ResidentRepositoryTest`, which causes Laravel to run all migrations fresh before each test and roll them back after, ensuring a clean and isolated database state for every test.

---

## My Student-Designed Test

**Test name:** `test_contact_number_leading_zero_is_preserved_after_persistence`

**What it verifies:** After a Resident is saved and retrieved from the database, the contact number still begins with `0` and matches the original value `09171234567` exactly.

**Why I chose this scenario:** The T03 guide specifically warns that the leading zero of a contact number must not be lost during persistence. Storing it as a numeric column type would silently drop the leading zero. This test confirms that the `contact_number` column is stored as a string and the value is retrieved intact.

---

## Tools and References Used

- T03 Course Guide
- Laravel Documentation (Eloquent ORM, Migrations, Testing)
- Amazon Q (AI coding assistant — assisted with generating migration, repository, test file structure, and checkpoint document)

/*
   ==================================================
   RESET ID COLUMN AND ENABLE AUTO_INCREMENT
   ==================================================
   This script fixes:
   - "Field 'id' doesn't have a default value" error.
   - Duplicate or non-sequential 'id' values.
   - Missing AUTO_INCREMENT for new records.
   - Ensures all existing data remains intact.

   Steps:
   1. Reset 'id' to start from 0.
   2. Ensure 'id' is unique.
   3. Enable AUTO_INCREMENT for future inserts.
   4. Set AUTO_INCREMENT to start from 1.
*/

-- Step 1: Reset 'id' values in sequential order starting from 0
SET @count = -1;
UPDATE dbMessages SET id = (@count := @count + 1);

-- Step 2: Ensure 'id' is unique and enable AUTO_INCREMENT
ALTER TABLE dbMessages 
ADD UNIQUE (id),
MODIFY id INT NOT NULL AUTO_INCREMENT;

-- Step 3: Set AUTO_INCREMENT to start from 1 for future inserts
ALTER TABLE dbMessages AUTO_INCREMENT = 1;

-- Done: 'id' starts at 0, and new records will auto-increment.



-- Add the new column for the monthly community package
ALTER TABLE dbPointsProg 
ADD COLUMN monthly_community_package VARCHAR(3) NULL;

-- Modify to allow for varchar 5
ALTER TABLE dbPointsProg
MODIFY COLUMN monthly_community_package VARCHAR(5) NULL;

-- Drop these columns from the database
ALTER TABLE dbPointsProg 
DROP COLUMN photography,
DROP COLUMN house_projects,
DROP COLUMN financial_relief;

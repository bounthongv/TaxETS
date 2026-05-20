USE tax_ets;

-- Create the status table
CREATE TABLE IF NOT EXISTS enterprise_project_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed initial values
INSERT IGNORE INTO enterprise_project_status (status_name) VALUES 
('Active'), 
('Cancel'), 
('Pending'), 
('MOU Phase'), 
('FS Phase'),
('Inactive');

-- Update repo_moic table to use status_id
ALTER TABLE repo_moic ADD COLUMN status_id INT DEFAULT NULL;

-- Migrate existing data
UPDATE repo_moic SET status_id = (SELECT id FROM enterprise_project_status WHERE status_name = 'Active') WHERE is_active = 1;
UPDATE repo_moic SET status_id = (SELECT id FROM enterprise_project_status WHERE status_name = 'Inactive') WHERE is_active = 0;

-- Add foreign key
ALTER TABLE repo_moic ADD FOREIGN KEY (status_id) REFERENCES enterprise_project_status(id);

-- Optional: Keep is_active but maybe deprecated or synchronized. 
-- For now, we will use status_id in the UI.

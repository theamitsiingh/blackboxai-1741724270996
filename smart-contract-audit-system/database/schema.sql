-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    reset_token VARCHAR(255) NULL,
    reset_token_expires DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create audits table
CREATE TABLE audits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contract_name VARCHAR(255) NOT NULL,
    contract_address VARCHAR(255) NOT NULL,
    contract_network VARCHAR(100) NOT NULL,
    contract_source TEXT NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'rejected') NOT NULL DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    audit_type ENUM('full', 'quick', 'focused') NOT NULL DEFAULT 'full',
    submission_date DATETIME NOT NULL,
    completion_date DATETIME NULL,
    assigned_to INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create reports table
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_id INT NOT NULL,
    report_type ENUM('initial', 'follow_up', 'final') NOT NULL DEFAULT 'initial',
    findings TEXT NOT NULL,
    risk_level ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    recommendations TEXT NOT NULL,
    summary TEXT NOT NULL,
    detailed_analysis TEXT NOT NULL,
    generated_by INT NOT NULL,
    generated_at DATETIME NOT NULL,
    status ENUM('draft', 'pending_review', 'approved', 'rejected') NOT NULL DEFAULT 'draft',
    version INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create compliance table
CREATE TABLE compliance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    standard_name VARCHAR(255) NOT NULL,
    version VARCHAR(50) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT NOT NULL,
    validation_rules TEXT NOT NULL,
    severity_level ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    created_by INT NOT NULL,
    status ENUM('active', 'pending', 'archived') NOT NULL DEFAULT 'active',
    effective_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create audit_compliance table (for tracking compliance checks)
CREATE TABLE audit_compliance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_id INT NOT NULL,
    compliance_id INT NOT NULL,
    status ENUM('compliant', 'non_compliant', 'not_applicable') NOT NULL,
    notes TEXT NULL,
    checked_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE,
    FOREIGN KEY (compliance_id) REFERENCES compliance(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create audit_history table
CREATE TABLE audit_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    performed_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create compliance_history table
CREATE TABLE compliance_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    compliance_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    modified_by INT NOT NULL,
    modified_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (compliance_id) REFERENCES compliance(id) ON DELETE CASCADE,
    FOREIGN KEY (modified_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create report_feedback table
CREATE TABLE report_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    feedback TEXT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create indexes for better query performance
CREATE INDEX idx_audits_status ON audits(status);
CREATE INDEX idx_audits_priority ON audits(priority);
CREATE INDEX idx_reports_risk_level ON reports(risk_level);
CREATE INDEX idx_compliance_category ON compliance(category);
CREATE INDEX idx_compliance_status ON compliance(status);
CREATE INDEX idx_audit_compliance_status ON audit_compliance(status);

-- Add unique constraints
ALTER TABLE compliance ADD CONSTRAINT uq_compliance_standard_version 
    UNIQUE (standard_name, version);
ALTER TABLE reports ADD CONSTRAINT uq_report_audit_version 
    UNIQUE (audit_id, version);

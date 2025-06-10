-- Create sessions table for database session storage
-- This provides better session persistence and management

CREATE TABLE IF NOT EXISTS sessions (
    session_id VARCHAR(128) NOT NULL PRIMARY KEY,
    user_id CHAR(36) NULL,  -- NULL for anonymous sessions
    session_data TEXT NOT NULL,  -- Serialized session data
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45) NULL,  -- Support IPv4 and IPv6
    user_agent TEXT NULL,
    
    -- Indexes for performance
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity),
    INDEX idx_expires_at (expires_at),
    
    -- Foreign key constraint (optional - allows anonymous sessions)
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Clean up expired sessions (can be run as a cron job)
-- DELETE FROM sessions WHERE expires_at < NOW();

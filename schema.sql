
-- Table structure for alliances
CREATE TABLE alliances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255),
    power BIGINT,
    ranking INT,
    previous_ranking INT,
    points_difference BIGINT,
    gift_lvl INT,
    likes INT DEFAULT 0,
    last_updated DATETIME
);

-- Table structure for alliance_power_history
CREATE TABLE alliance_power_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alliance_id INT,
    power BIGINT,
    date_recorded DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for polls
CREATE TABLE polls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for poll_options
CREATE TABLE poll_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT,
    option_text VARCHAR(255),
    votes INT DEFAULT 0
);

-- Table structure for poll_votes
CREATE TABLE poll_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT,
    option_id INT,
    user_ip VARCHAR(255),
    user_agent TEXT,
    user_id INT
);

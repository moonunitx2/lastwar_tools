#!/bin/bash
# Script to set up the project

echo "Setting up project..."

# Prompt for DB credentials
read -p "MySQL Host: " DB_HOST
read -p "MySQL User: " DB_USER
read -s -p "MySQL Password: " DB_PASS
echo
read -p "Database Name: " DB_NAME

# Generate db.php
cat <<EOT > db.php
<?php
$host = "$DB_HOST";
$username = "$DB_USER";
$password = "$DB_PASS";
$dbname = "$DB_NAME";

$conn = new mysqli($host, $username, $password, $dbname);

if (\$conn->connect_error) {
    die("Connection failed: " . \$conn->connect_error);
}
?>
EOT

# Run composer install
if [ -f "composer.json" ]; then
  echo "Installing Composer dependencies..."
  composer install
fi

# Import schema
read -p "Do you want to import the default SQL schema? (y/n): " answer
if [[ "$answer" == "y" ]]; then
  mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < schema.sql
  echo "Database imported."
fi

echo "Setup complete."

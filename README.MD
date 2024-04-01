**Prayer Time Generator**

**Setup:**
Before setting up the Prayer Time Generator, ensure you have the following prerequisites installed:
- PHP 8.2 or higher
- Composer

**Requirement:**
- PHP 8.2 or higher
- Composer

**Installation:**
1. Clone the repository:
   ```
   https://github.com/tri-suli/prayer-time-reminder.git
   ```
2. Navigate to the project directory:
   ```
   cd prayer-time-generator
   ```
3. Install dependencies:
   ```
   composer install
   ```

**How to Use the App:**
1. Configure the environment variables:
    - Copy the `.env.example` file and rename it to `.env`.
    - Update the database configuration in the `.env` file.

2Start the application:
   ```
   php index.php
   ```

3Interact with the application using the provided commands:
    - Use the command `subs [--id=SUB_ID]` to display subscriber records.
    - Use the command `subs boxes [--id=SUB_ID]` to display subscriber list with music boxes.
    - Use the command `songs [--id=SUB_ID]` to display subscriber songs with music boxes.
    - Use the command `songs:update` to update prayer times boxes.
    - Use the command `notify [--zones=PRAYER_ZONE]` to display prayer time message based on prayer zones.
    - Use the commands `e`, `q`, `exit`, or `quit` to close the application.

**Note:** Ensure you have PHP 8.2 or higher installed to run the application successfully.

Feel free to explore and utilize the Prayer Time Generator application according to the provided instructions.
<?php

class EnvEncryptor
{
    private const ALGO = 'aes-256-gcm';
    private const SALT_LENGTH = 16;
    private const IV_LENGTH = 12;
    private const TAG_LENGTH = 16;
    private const ITERATIONS = 100000;

    public function run(string $action): void
    {
        if (!in_array($action, ['encrypt', 'decrypt'])) {
            echo "Usage: php env-sec.php [encrypt|decrypt]\n";
            exit(1);
        }

        echo "Welcome to Secure Env Tool\n";
        
        $appKey = $this->promptHidden("Enter APP_KEY: ");
        $pin = $this->promptHidden("Enter secret PIN: ");

        if (empty($appKey) || empty($pin)) {
            echo "\nError: APP_KEY and PIN cannot be empty.\n";
            exit(1);
        }

        $password = $appKey . $pin;

        if ($action === 'encrypt') {
            $this->encrypt('.env', '.env.encrypted', $password);
        } else {
            $this->decrypt('.env.encrypted', '.env.decrypted', $password);
        }
    }

    private function encrypt(string $inputFile, string $outputFile, string $password): void
    {
        if (!file_exists($inputFile)) {
            echo "\nError: Input file '$inputFile' not found.\n";
            exit(1);
        }

        $plaintext = file_get_contents($inputFile);

        // Generate salt and IV
        $salt = random_bytes(self::SALT_LENGTH);
        $iv = random_bytes(self::IV_LENGTH);

        // Derive key using PBKDF2
        $key = $this->deriveKey($password, $salt);

        // Encrypt with AES-256-GCM
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            self::ALGO,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            echo "\nError: Encryption failed.\n";
            exit(1);
        }

        // Output format: [Salt(16)][IV(12)][Ciphertext][Tag(16)]
        $finalData = $salt . $iv . $ciphertext . $tag;

        // Save as binary file
        file_put_contents($outputFile, $finalData);

        echo "\n[SUCCESS] Encrypted '$inputFile' to '$outputFile' successfully.\n";
    }

    private function decrypt(string $inputFile, string $outputFile, string $password): void
    {
        if (!file_exists($inputFile)) {
            echo "\nError: Input file '$inputFile' not found.\n";
            exit(1);
        }

        $data = file_get_contents($inputFile);

        // Validate length
        $minLen = self::SALT_LENGTH + self::IV_LENGTH + self::TAG_LENGTH;
        if (strlen($data) < $minLen) {
            echo "\nError: File '$inputFile' is corrupted or invalid format.\n";
            exit(1);
        }

        // Extract parts
        $salt = substr($data, 0, self::SALT_LENGTH);
        $iv = substr($data, self::SALT_LENGTH, self::IV_LENGTH);
        $tag = substr($data, -self::TAG_LENGTH);
        $ciphertext = substr($data, self::SALT_LENGTH + self::IV_LENGTH, -self::TAG_LENGTH);

        // Derive key using PBKDF2
        $key = $this->deriveKey($password, $salt);

        // Decrypt
        $plaintext = openssl_decrypt(
            $ciphertext,
            self::ALGO,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            echo "\n[CRITICAL ERROR] Decryption failed! The password is wrong or the file has been tampered with!\n";
            exit(1);
        }

        file_put_contents($outputFile, $plaintext);
        echo "\n[SUCCESS] Decrypted '$inputFile' to '$outputFile' successfully.\n";
    }

    private function deriveKey(string $password, string $salt): string
    {
        return hash_pbkdf2('sha256', $password, $salt, self::ITERATIONS, 32, true);
    }

    private function promptHidden(string $prompt): string
    {
        echo $prompt;
        
        // Hide terminal output
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $input = trim(fgets(STDIN));
        } else {
            system('stty -echo');
            $input = trim(fgets(STDIN));
            system('stty echo');
            echo "\n";
        }
        
        return $input;
    }
}

// Bootstrap
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the CLI.");
}

$action = $argv[1] ?? '';
$encryptor = new EnvEncryptor();
$encryptor->run($action);

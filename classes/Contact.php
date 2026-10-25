<?php
class Contact
{
    private $db;
    private $error = '';

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getError()
    {
        return $this->error;
    }

    private function fail($message)
    {
        $this->error = $message;
        return false;
    }

    public function send($form)
    {
        $name = inputText($form, 'full_name');
        $email = inputText($form, 'email');
        $phone = inputText($form, 'phone');
        $subject = inputText($form, 'subject');
        $message = inputText($form, 'message');
        if ($name === '' || mb_strlen($name) > 100) {
            return $this->fail('Enter your name (up to 100 characters).');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            return $this->fail('Enter a valid email address.');
        }
        if ($phone !== '' && !validPhone($phone)) {
            return $this->fail('Enter a valid phone number.');
        }
        if (!in_array($subject, ['booking', 'account', 'provider', 'feedback', 'other'])) {
            return $this->fail('Choose a subject.');
        }
        if (mb_strlen($message) < 10 || mb_strlen($message) > 3000) {
            return $this->fail('Enter a message between 10 and 3,000 characters.');
        }
        $stmt = $this->db->prepare(
            'INSERT INTO contact_messages (full_name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)'
        );
        if (!$stmt->execute([$name, $email, $phone, $subject, $message])) {
            return $this->fail('Could not save your message.');
        }
        return true;
    }
}

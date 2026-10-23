<?php
class Contact
{
    public function __construct(private PDO $db) {}
    public function send(array $data): void
    {
        foreach (['full_name','email','phone','subject','message'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field])) throw new RuntimeException('Please complete the form.');
            $data[$field] = trim($data[$field]);
            if (!mb_check_encoding($data[$field], 'UTF-8')) throw new RuntimeException('Invalid text.');
        }
        if ($data['full_name'] === '' || mb_strlen($data['full_name']) > 100) throw new RuntimeException('Enter your name (up to 100 characters).');
        if (!filter_var($data['email'],FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 190) throw new RuntimeException('Enter a valid email address.');
        if ($data['phone'] !== '' && !preg_match('/^[+0-9() .-]{7,20}$/D',$data['phone'])) throw new RuntimeException('Enter a valid phone number.');
        if (!in_array($data['subject'],['booking','account','provider','feedback','other'],true)) throw new RuntimeException('Choose a subject.');
        if ($data['message'] === '' || strlen($data['message']) > 10000) throw new RuntimeException('Enter a message up to 10000 bytes.');
        $this->db->prepare('INSERT INTO contact_messages (full_name,email,phone,subject,message) VALUES (?,?,?,?,?)')
            ->execute([$data['full_name'],$data['email'],$data['phone'],$data['subject'],$data['message']]);
    }
}

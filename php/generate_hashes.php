<?php
$pwds = [
    'admin123',
    'teacher123',
    'sec123',
    'canteen123',
    'disb123',
    'adas123',
];
foreach ($pwds as $pwd) {
    echo $pwd . ' => ' . password_hash($pwd, PASSWORD_DEFAULT) . "\n";
}

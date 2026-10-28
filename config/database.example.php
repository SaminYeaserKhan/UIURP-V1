<?php
// Copy this file to config/database.php and put your own connection string in it.
// config/database.php is ignored by git, so the password never reaches the repository.
return [
    // MongoDB Atlas: Database → Connect → Drivers, then copy the connection string.
    'mongodb_uri' => 'mongodb+srv://USERNAME:PASSWORD@YOUR-CLUSTER.mongodb.net/uiurp?retryWrites=true&w=majority',

    // Or a local MongoDB server:
    // 'mongodb_uri' => 'mongodb://localhost:27017/uiurp',
];

<?php
// Copy this file to config/secrets.php and fill in your own values.
// config/secrets.php is ignored by git, so these values never reach the repository.
// Each one can also be set as an environment variable instead (shown in brackets).
return [
    // [MONGODB_URI] MongoDB Atlas: Database → Connect → Drivers, then copy the connection string.
    'mongodb_uri' => 'mongodb+srv://USERNAME:PASSWORD@YOUR-CLUSTER.mongodb.net/uiurp?retryWrites=true&w=majority',
    // Or a local MongoDB server:
    // 'mongodb_uri' => 'mongodb://localhost:27017/uiurp',

    // [GEMINI_API_KEY] Google Gemini API key for the literature matrix,
    // from https://aistudio.google.com/apikey. Leave empty to turn the AI summaries off.
    'gemini_api_key' => '',

    // [GEMINI_MODEL] Optional: which Gemini model the literature matrix uses.
    // 'gemini_model' => 'gemini-2.5-flash-lite',
];

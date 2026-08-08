<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>家計簿アプリ</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.ts',
    ])
</head>

<body>
    <div id="app"></div>
</body>
</html>

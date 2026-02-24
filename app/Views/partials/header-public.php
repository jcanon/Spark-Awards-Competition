<!DOCTYPE html>
<html lang="<?= esc(service('request')->getLocale() ?: 'en') ?>">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc(($title ?? $settings['title'])) ?></title>

    <link href="/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="/css/sb-admin-2.css" rel="stylesheet">
</head>
<body class="bg-light">

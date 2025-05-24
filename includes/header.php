<?php
session_start();
?>
<header class="bg-white shadow-md">
    <nav class="container mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
            <a href="index.php" class="text-xl font-bold text-gray-800">Worker Week Compass</a>
            <div class="space-x-4">
                <a href="index.php?page=home" class="text-gray-600 hover:text-gray-900">Home</a>
                <a href="index.php?page=tasks" class="text-gray-600 hover:text-gray-900">Aufgaben</a>
                <a href="index.php?page=calendar" class="text-gray-600 hover:text-gray-900">Kalender</a>
                <a href="index.php?page=profile" class="text-gray-600 hover:text-gray-900">Profil</a>
            </div>
        </div>
    </nav>
</header> 
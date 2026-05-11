<script setup>
// Mengganti emoji dengan URL logo dari repositori GitHub
const appIcon = 'https://raw.githubusercontent.com/rahmadwidiansyah/script_pencatat_keuangan/refs/heads/main/logo.png';
</script>

<template>
    <!-- Pastikan class Tailwind ditutup dengan benar dan tambahkan styling untuk logo -->
    <div class="flex items-center justify-center bg-gradient-to-br from-gray-900 to-gray-800 p-4">
        <img :src="appIcon" alt="App Icon" class="w-12 h-12 object-contain" />
    </div>
</template>
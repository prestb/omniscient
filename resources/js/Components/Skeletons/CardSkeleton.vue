<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden animate-pulse">
        <!-- Image Skeleton -->
        <div class="w-full h-48 bg-gray-200"></div>
        
        <!-- Content Skeleton -->
        <div class="p-4 space-y-3">
            <!-- Title -->
            <div class="h-5 bg-gray-200 rounded w-3/4"></div>
            
            <!-- Subtitle -->
            <div class="h-4 bg-gray-200 rounded w-1/2"></div>
            
            <!-- Rating -->
            <div class="flex items-center gap-2">
                <div class="h-4 bg-gray-200 rounded w-24"></div>
                <div class="h-4 bg-gray-200 rounded w-8"></div>
            </div>
            
            <!-- Location -->
            <div class="h-4 bg-gray-200 rounded w-2/3"></div>
            
            <!-- Tags -->
            <div class="flex gap-2">
                <div class="h-6 bg-gray-200 rounded w-16"></div>
                <div class="h-6 bg-gray-200 rounded w-20"></div>
            </div>
        </div>
    </div>
</template>
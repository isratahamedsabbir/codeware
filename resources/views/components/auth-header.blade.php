@props(['title', 'description'])

<div class="flex w-full flex-col text-center text-white">  
    <flux:heading class="text-white!" size="xl">{{ $title }}</flux:heading> 
    <flux:subheading class="text-white!">{{ $description }}</flux:subheading> 
</div>  

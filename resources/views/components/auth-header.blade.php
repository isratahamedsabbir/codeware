@props(['title', 'description'])

<div class="flex w-full flex-col text-center">
    <flux:heading class="!text-gray-800" size="xl">{{ $title }}</flux:heading>
    <flux:subheading class="!text-gray-600">{{ $description }}</flux:subheading>
</div>

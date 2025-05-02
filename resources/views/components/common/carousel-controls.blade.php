@props(['prevId', 'nextId'])

<div class="carousel-controls">
    <button id="{{ $prevId }}" class="carousel-prev"> <span class="icon-prev"></span> </button>
    <button id="{{ $nextId }}" class="carousel-next"> <span class="icon-next"></span> </button>
</div>

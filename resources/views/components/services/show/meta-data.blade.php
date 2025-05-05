@props(['service'])
<ul class="single-market__info">
    <li class="icon-view">{{ number_format($service['views']) }}</li>
    <li class="icon-link">{{ number_format($service['transitions']) }}</li>
    <li class="icon-star">{{ $service['rating'] }}</li>
</ul>
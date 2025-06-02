@extends('layouts.main')

@section('title', 'Успешная отписка')

@section('page-content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
                    <h3 class="mb-3">Отписка выполнена успешно</h3>
                    <p class="lead text-muted mb-4">
                        Вы больше не будете получать рассылку от нас.
                    </p>
                    <p class="text-muted mb-4">
                        Если вы передумаете, вы сможете подписаться снова в настройках профиля.
                    </p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('home') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-home me-2"></i>
                            На главную
                        </a>
                        @auth
                        <a href="{{ route('profile.settings') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-user-cog me-2"></i>
                            Настройки профиля
                        </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
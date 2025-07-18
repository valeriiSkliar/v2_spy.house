#!/bin/bash

# Queue Workers Persistent Startup Script
# This script starts queue workers that run indefinitely without time limits

# Create logs directory if it doesn't exist
mkdir -p storage/logs

# Function to start a worker with persistent settings
start_persistent_worker() {
    local queue=$1
    local log_file=$2
    local sleep_time=${3:-3}
    local tries=${4:-3}
    local timeout=${5:-300}
    
    php artisan queue:work rabbitmq \
        --queue="$queue" \
        --sleep="$sleep_time" \
        --tries="$tries" \
        --timeout="$timeout" \
        --memory=512 \
        --daemon \
        >> "$log_file" 2>&1 &
    
    echo "Started worker for queue: $queue (PID: $!)"
    echo $! >> storage/logs/worker-pids.txt
}

# Function to check if workers are running
check_workers() {
    if [ -f storage/logs/worker-pids.txt ]; then
        echo "Checking running workers..."
        while read -r pid; do
            if ps -p "$pid" > /dev/null 2>&1; then
                echo "Worker PID $pid is running"
            else
                echo "Worker PID $pid is not running"
            fi
        done < storage/logs/worker-pids.txt
    else
        echo "No worker PIDs file found"
    fi
}

# Function to stop all workers
stop_workers() {
    if [ -f storage/logs/worker-pids.txt ]; then
        echo "Stopping all workers..."
        while read -r pid; do
            if ps -p "$pid" > /dev/null 2>&1; then
                kill -TERM "$pid"
                echo "Sent TERM signal to worker PID $pid"
            fi
        done < storage/logs/worker-pids.txt
        rm -f storage/logs/worker-pids.txt
        echo "All workers stopped"
    else
        echo "No worker PIDs file found"
    fi
}

# Function to start all workers
start_all_workers() {
    # Check if workers are already running
    if [ -f storage/logs/worker-pids.txt ]; then
        echo "Checking for existing workers..."
        running_count=0
        while read -r pid; do
            if ps -p "$pid" > /dev/null 2>&1; then
                running_count=$((running_count + 1))
            fi
        done < storage/logs/worker-pids.txt
        
        if [ $running_count -gt 0 ]; then
            echo "WARNING: $running_count workers are already running!"
            echo "Use './start-workers.sh stop' first or './start-workers.sh restart' to restart all workers."
            echo "Current running workers:"
            check_workers
            exit 1
        fi
    fi

    # Clear previous PIDs file
    rm -f storage/logs/worker-pids.txt

    echo "Starting persistent queue workers..."
    echo "Started at: $(date)"

    # Start workers for default queue (2 workers)
    start_persistent_worker "default" "storage/logs/worker-default.log" 3 3 300
    start_persistent_worker "default" "storage/logs/worker-default.log" 3 3 300

    # Start workers for collect-ads queue (2 workers with longer timeout for heavy processing)
    start_persistent_worker "collect-ads" "storage/logs/worker-collect-ads.log" 5 5 600
    start_persistent_worker "collect-ads" "storage/logs/worker-collect-ads.log" 5 5 600

    # Start workers for push-house-ads queue (1 worker)
    start_persistent_worker "push-house-ads" "storage/logs/worker-push-house-ads.log" 5 5 600

    # Start workers for delayed queue (1 worker)
    start_persistent_worker "delayed" "storage/logs/worker-delayed.log" 3 3 300

    # Start workers for mail queue (2 workers with high priority settings)
    start_persistent_worker "mail" "storage/logs/worker-mail.log" 1 3 120
    start_persistent_worker "mail" "storage/logs/worker-mail.log" 1 3 120

    # Start worker for website-downloads queue (1 worker)
    start_persistent_worker "website-downloads" "storage/logs/worker-downloads.log" 3 3 300

    echo ""
    echo "=========================================="
    echo "All persistent queue workers have been started!"
    echo "Worker PIDs saved to: storage/logs/worker-pids.txt"
    echo "Logs location: storage/logs/"
    echo ""
    echo "Management commands:"
    echo "  Check workers: bash start-workers.sh check"
    echo "  Stop workers:  bash start-workers.sh stop"
    echo "  Restart:       bash start-workers.sh restart"
    echo "=========================================="
}

# Handle command line arguments FIRST
case "${1:-start}" in
    "check")
        check_workers
        exit 0
        ;;
    "stop")
        stop_workers
        exit 0
        ;;
    "restart")
        stop_workers
        sleep 2
        start_all_workers
        exit 0
        ;;
    "start"|"")
        start_all_workers
        echo "Workers started successfully!"
        exit 0
        ;;
    *)
        echo "Usage: $0 {start|stop|check|restart}"
        echo "  start   - Start all queue workers (default)"
        echo "  stop    - Stop all running workers"
        echo "  check   - Check status of running workers"
        echo "  restart - Stop and restart all workers"
        exit 1
        ;;
esac

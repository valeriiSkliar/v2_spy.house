/* mytimer.c – an HTTrack plugin to stop after 3 minutes */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>
#include "httrack-library.h"
#include "htsopt.h"
#include "htsdefines.h"

/* Global variable to store the start time */
static time_t start_time = 0;

/* 
 * Loop callback: this function is called periodically during the mirror.
 * If 3 minutes (180 seconds) have elapsed, we print a message and return 0
 * (which gives a successful exit code).
 */
static int time_limit_loop(t_hts_callbackarg *carg, httrackp *opt,
                           lien_back* back, int back_max, int back_index,
                           int lien_tot, int lien_ntot, int stat_time,
                           hts_stat_struct* stats) {
    time_t now = time(NULL);
    if (start_time == 0) {
        start_time = now; /* safeguard if not set in hts_plug */
    }
    if (difftime(now, start_time) >= 180.0) {
        fprintf(stderr, "Time limit of 3 minutes reached. Stopping mirror gracefully.\n");
        return 0; /* Tell HTTrack to stop */
    }
    return 1; /* continue mirror otherwise */
}

/*
 * Optional end callback – called when the mirror ends.
 * Here you could perform cleanup. In our case, we just print a message.
 */
static int my_end_callback(t_hts_callbackarg *carg, httrackp *opt) {
    fprintf(stderr, "Mirror ended (or aborted by timer) successfully.\n");
    return 1; /* signal success */
}

/*
 * Module entry point.
 * This function is called when the plugin is loaded.
 * We set our start time and plug our callbacks.
 */
EXTERNAL_FUNCTION int hts_plug(httrackp *opt, const char* argv) {
    /* record the start time */
    start_time = time(NULL);
    
    /* plug our loop callback – this will be called periodically */
    CHAIN_FUNCTION(opt, loop, time_limit_loop, NULL);
    
    /* plug our end callback so that if the mirror ends due to our timer, it returns success */
    CHAIN_FUNCTION(opt, end, my_end_callback, NULL);
    
    return 1;  /* success */
}

/*
 * Module exit point.
 */
EXTERNAL_FUNCTION int hts_unplug(httrackp *opt) {
    fprintf(stderr, "Plugin unplugged.\n");
    return 1;  /* success */
}

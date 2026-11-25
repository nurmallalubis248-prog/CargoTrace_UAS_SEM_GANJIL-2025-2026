# Airport METAR on the dashboard conditions panel

The dashboard "conditions" panel combines three sources, none of which needs an API key:

- **Weather and sea state**: Open-Meteo (called from the browser).
- **Official airport METAR**: NOAA Aviation Weather Center, fetched by `DashboardOverviewController::metar()` and cached for 10 minutes.
- Both are operational indicators only; they do not replace NOTAMs or official port-authority information.

## Mapping airports to ICAO codes
Airport codes in the `locations` table are mapped to 4-letter ICAO codes in the `ICAO` constant of the controller (for example `SGSIA` -> `WSSS`, `AEDXB` -> `OMDB`, `IDCGK` -> `WIII`). To support a new airport, add its pair to that constant, or store the ICAO code directly as the location code.

After changing the controller or view, run `php artisan route:clear` and `php artisan view:clear`.

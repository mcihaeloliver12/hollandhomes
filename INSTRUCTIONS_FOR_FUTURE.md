## Future Deploy Notes

1. Run the usual `git status`, `git add`, and `git commit` workflow before pushing to `origin/main`.
2. Deploy using the existing tar+ssh pipeline: `COPYFILE_DISABLE=1 tar -czf - --exclude .git --exclude .DS_Store . | ssh root@76.13.113.174 "tar -xzf - -C /var/www/hollandhomes"`.
3. The property and homepage headers each have custom mobile-nav toggles; update both `index.php` and `property.php` if you change either navigation area.
4. Amenity data is pulled via `includes/AirbnbScraper.php` from Airbnb’s `seeAllAmenitiesGroups` JSON. Cache files live under `cache/airbnb_*.json`.
5. When refreshing images, keep the `Home/Photos`, `Chalet/Photos`, and `Villa/Photos` directories in sync with the `galleryPhotos` logic in `property.php`.

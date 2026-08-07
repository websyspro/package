@echo off
SET VERSION=1.0.5

git add .
git commit -m "Release v%VERSION%"
git tag v%VERSION%
git push origin HEAD
git push origin v%VERSION%

echo.
echo Released v%VERSION% successfully!

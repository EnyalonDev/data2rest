#!/bin/bash
# DATA2REST - Auditoría Técnica Completa
# Generado: 2026-01-16

echo "==================================="
echo "AUDITORÍA TÉCNICA DATA2REST"
echo "==================================="
echo ""

# 1. Análisis de código PHP
echo "1. ANÁLISIS DE CÓDIGO PHP"
echo "-------------------------"
echo "Total de archivos PHP:"
find src -name "*.php" -type f | wc -l

echo ""
echo "Total de funciones definidas:"
grep -r "function " src --include="*.php" | wc -l

echo ""
echo "Búsqueda de funciones deprecated:"
grep -rn "mysql_\|ereg\|split(\|each(" src --include="*.php" | wc -l

echo ""
echo "2. ANÁLISIS DE INTERNACIONALIZACIÓN"
echo "------------------------------------"
echo "Búsqueda de texto hardcoded en controladores:"
grep -rn "echo\s*['\"]" src/Modules --include="*.php" | head -20

echo ""
echo "3. ANÁLISIS DE DOCUMENTACIÓN"
echo "-----------------------------"
echo "Archivos .md encontrados:"
find docs -name "*.md" -type f | wc -l

echo ""
echo "4. ANÁLISIS DE ESTRUCTURA"
echo "-------------------------"
ls -lh src/Modules/

echo ""
echo "5. ANÁLISIS DE VISTAS BLADE"
echo "---------------------------"
find src/Views -name "*.blade.php" -type f | wc -l

echo ""
echo "==================================="
echo "AUDITORÍA COMPLETADA"
echo "==================================="

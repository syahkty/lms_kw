function generateMoodlePattern(seed) {
    // 1. Pastikan seed selalu berupa string dan tangani jika datanya kosong
    const seedText = String(seed || "default_course"); 
    
    let hash = 0;
    for (let i = 0; i < seedText.length; i++) {
        hash = seedText.charCodeAt(i) + ((hash << 5) - hash);
    }
    
    const hue = Math.abs(hash % 360);
    const baseColor = `hsl(${hue}, 75%, 55%)`;

    let svg = `<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">`;
    svg += `<rect width="100%" height="100%" fill="${baseColor}" />`;

    const gridSize = 20;
    const squareSize = 200 / gridSize;
    let randomSeed = Math.abs(hash);
    
    function random() {
        let x = Math.sin(randomSeed++) * 10000;
        return x - Math.floor(x);
    }

    for (let x = 0; x < gridSize; x++) {
        for (let y = 0; y < gridSize; y++) {
            const isDark = random() > 0.5;
            const fill = isDark ? '#000000' : '#ffffff';
            const opacity = (random() * 0.1).toFixed(3); 
            
            svg += `<rect x="${x * squareSize}" y="${y * squareSize}" width="${squareSize}" height="${squareSize}" fill="${fill}" opacity="${opacity}" />`;
        }
    }
    svg += `</svg>`;

    return `data:image/svg+xml;base64,${btoa(svg)}`;
}
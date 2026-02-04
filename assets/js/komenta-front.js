class KomentaWindow {
    getAllKomentaInstances() {
        return Array.from(document.querySelectorAll('.container-kommenta-inline'));
    }
    parseAllKomentaEmotions(parentNode) {
        return Array.from(parentNode.querySelectorAll('.emotion-reaction'));
    }
    getTooltipInstance(parentNode) {
        return parentNode.querySelector('.emotion-tooltip');
    }
}
document.addEventListener('DOMContentLoaded', () => {
    window.komenta=new KomentaWindow();
    let allKomentaInstances=komenta.getAllKomentaInstances();
    console.log(allKomentaInstances);
    for(let i=0;i<allKomentaInstances.length;i++)
    {
        console.log(allKomentaInstances[i]);
        let allEmotions=komenta.parseAllKomentaEmotions(allKomentaInstances[i]);
        let tooltipCurrent=komenta.getTooltipInstance(allKomentaInstances[i]);
        let tooltipEmotion=tooltipCurrent.querySelector('.badge-emotion');
        let tooltipText=tooltipCurrent.querySelector('.emotion-name');
        // Adding event listener on each emotions
        allEmotions.map((emotion) => {
            let emotionColor=emotion.style.background;
            let emotionText=emotion.getAttribute('data-label');
            console.log('Emotion text', emotionText);
            emotion.addEventListener("mousemove", (el) => {
                const containerRect=allKomentaInstances[i].getBoundingClientRect();
                tooltipEmotion.style.background=emotionColor;
                tooltipCurrent.style.borderColor=emotionColor;
                tooltipCurrent.style.transform='translateY(0px)';
                tooltipCurrent.style.opacity='1';
                const x=el.clientX-containerRect.left-(tooltipCurrent.offsetWidth/2);
                const y=el.clientY-containerRect.top-tooltipCurrent.offsetHeight+55;
                tooltipCurrent.style.left=`${x}px`;
                tooltipCurrent.style.top=`${y}px`;
                
                // Injecting the content
                console.log(tooltipText);
                tooltipText.innerText=`${emotionText} (15 votes)`;
            });
            emotion.addEventListener("mouseout", (el) => {
                
                setTimeout(() => { tooltipCurrent.style.opacity='0';tooltipCurrent.style.transform='translateY(20px)';                }, 400);
            });
        });
    }
});
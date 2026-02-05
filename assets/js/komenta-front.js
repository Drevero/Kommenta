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
    async submitVote(idComment, labelEmotion) {
        let commentContainer=document.querySelector(`.container-kommenta-inline[data-comment-id="${idComment}"]`);
        let loaderComment=commentContainer.querySelector('.loader-comment');
        loaderComment.style.display='block';
        try {
            const formData = new FormData();
            formData.append('action', 'komenta_vote');
            formData.append('nonce', komentaData.nonce);
            formData.append('id_comment', idComment);
            formData.append('reaction', labelEmotion);

            let callVote=await fetch(komentaData.ajaxUrl, {
                method: "POST",
                body: formData
            });
            let callResponse=await callVote.json();
            if(callResponse.success) {
                // Success
                // Refreshing vote DOM 
                if(callResponse.data.success) {
                    this.showToast("+ 1 Vote envoyé", 'success', idComment);
                    const reactions=callResponse.data.reactions;
                    const total=Object.values(reactions).reduce((sum, n) => sum + Number(n), 0);
                    commentContainer.querySelector('.total-vote-count span').innerText=total;
                    Object.keys(reactions).forEach((reaction) => {
                        const el=commentContainer.querySelector(`.emotion-reaction[data-reaction="${reaction}"]`);
                        if(el) {
                            const count=reactions[reaction];
                            el.setAttribute('data-number', count);
                            el.style.width=total > 0 ? `${(Number(count)*100/total)}%` : '0%';
                        }
                    });
                } else {
                    this.showToast("Vous avez déjà voté pour ce post", 'failure', idComment);
                }
                return;
            }
            // Error
        } catch (_) {
            this.showToast("Une erreur système nous empêche de compter votre vote", 'failure', idComment);
            console.error('Error during sending vote', _);
        } finally {
            loaderComment.style.display='none';
        }
    }
    showToast(toastText, type, commentID) {
        let toastComment=document.querySelector(`.container-kommenta-inline[data-comment-id="${commentID}"] .toast-comment`);
        toastComment.innerText=toastText;
        toastComment.setAttribute('type-toast', type);
        toastComment.classList.add('show-toast');
        setTimeout(() => {
            toastComment.classList.remove('show-toast');
        }, 800);
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
            emotion.addEventListener("mousemove", (el) => {
                const containerRect=allKomentaInstances[i].getBoundingClientRect();
                tooltipEmotion.style.background=emotionColor;
                tooltipCurrent.style.borderColor=emotionColor;
                tooltipCurrent.style.transform='translateY(0px)';
                tooltipCurrent.style.opacity='1';
                let numberVote=emotion.getAttribute('data-number');
                const x=el.clientX-containerRect.left-(tooltipCurrent.offsetWidth/2);
                const y=el.clientY-containerRect.top-tooltipCurrent.offsetHeight+55;
                tooltipCurrent.style.left=`${x}px`;
                tooltipCurrent.style.top=`${y}px`;

                // Injecting the content
                tooltipText.innerText=`${emotionText} (${numberVote} votes)`;
            });

            emotion.addEventListener("click", () => {
                komenta.submitVote(allKomentaInstances[i].getAttribute('data-comment-id'), emotion.getAttribute('data-reaction'));
            });
            emotion.addEventListener("mouseout", (el) => {
                
                setTimeout(() => { tooltipCurrent.style.opacity='0';tooltipCurrent.style.transform='translateY(20px)';                }, 400);
            });
        });
    }
});
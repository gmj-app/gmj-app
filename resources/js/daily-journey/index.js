import { createGame } from './game';

export async function mountDailyJourney(root){
 const expanded=root.querySelector('[data-game-expanded]'),parent=root.querySelector('[data-game-parent]'),status=root.querySelector('[data-game-status]'),csrf=document.querySelector('meta[name="csrf-token"]')?.content,base=root.dataset.issueUrl;
 let game=null,muted=localStorage.getItem('dailyJourneyMuted')==='1';
 const say=m=>status.textContent=m; const post=async(url,body)=>{const response=await fetch(url,{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify(body)});const data=await response.json().catch(()=>({}));if(!response.ok)throw new Error(data.message||Object.values(data.errors||{})[0]?.[0]||'Could not complete the request.');return data;};
 const issue=async()=>{say('Preparing a secure run…');return post(base,{});};
 const tone=(frequency,duration)=>{if(muted)return;const AudioContext=window.AudioContext||window.webkitAudioContext;if(!AudioContext)return;const ctx=new AudioContext(),osc=ctx.createOscillator(),gain=ctx.createGain();osc.frequency.value=frequency;gain.gain.value=.035;osc.connect(gain).connect(ctx.destination);osc.start();osc.stop(ctx.currentTime+duration);osc.onended=()=>ctx.close();};
 const launch=async()=>{game?.destroy(true);parent.replaceChildren();const session=await issue();game=createGame(parent,session,{base,post,status:say,tone,toggleMute:()=>{muted=!muted;localStorage.setItem('dailyJourneyMuted',muted?'1':'0');say(muted?'Sound muted.':'Sound on.');},restart:launch});};
 await launch();
 root.querySelector('[data-game-close]').onclick=()=>{game?.destroy(true);game=null;expanded.hidden=true;document.body.classList.remove('overflow-hidden');root.querySelector('[data-game-play]').focus();};
 const dispatch=name=>window.dispatchEvent(new Event(name));const jump=root.querySelector('[data-game-jump]'),duck=root.querySelector('[data-game-duck]');jump.addEventListener('pointerdown',e=>{e.preventDefault();dispatch('daily-journey-jump');});duck.addEventListener('pointerdown',e=>{e.preventDefault();dispatch('daily-journey-duck-start');});['pointerup','pointercancel','pointerleave'].forEach(name=>duck.addEventListener(name,e=>{e.preventDefault();dispatch('daily-journey-duck-end');}));
}

(() => {
  const source = document.getElementById('reports-data');
  if (!source) return;
  let data = {}; try { data = JSON.parse(source.textContent || '{}'); } catch (_) { return; }
  const palette = ['#0074c7','#ff5300','#73bc25','#f6bd00','#31b4e7','#6f42c1','#d84343','#00a6a6','#005b96'];
  const css = name => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  function setup(id) {
    const canvas = document.getElementById(id); if (!canvas) return null;
    const ratio = window.devicePixelRatio || 1, rect = canvas.getBoundingClientRect();
    const width = Math.max(300, Math.floor(rect.width)), height = Math.max(220, Math.floor(rect.height));
    canvas.width = width * ratio; canvas.height = height * ratio;
    const ctx = canvas.getContext('2d'); ctx.setTransform(ratio,0,0,ratio,0,0); ctx.clearRect(0,0,width,height);
    return {ctx,width,height};
  }
  function empty(box) { const {ctx,width,height}=box; ctx.fillStyle=css('--muted')||'#657b8c';ctx.font='14px Ubuntu Sans,Arial';ctx.textAlign='center';ctx.fillText(document.documentElement.lang==='en'?'No data yet':(document.documentElement.lang==='pt'?'Ainda não há dados':'Sin datos todavía'),width/2,height/2); }
  function bars(id, chart) {
    const box=setup(id); if(!box)return;const {ctx,width,height}=box,labels=chart?.labels||[],values=(chart?.values||[]).map(Number),max=Math.max(0,...values);if(!max)return empty(box);
    const left=52,right=20,top=25,bottom=58,w=width-left-right,h=height-top-bottom;
    ctx.strokeStyle=css('--line')||'#d8e3ea';for(let i=0;i<=4;i++){const y=top+h*i/4;ctx.beginPath();ctx.moveTo(left,y);ctx.lineTo(width-right,y);ctx.stroke();}
    const gap=w/Math.max(labels.length,1),bar=Math.min(50,gap*.58);
    labels.forEach((label,i)=>{const bh=values[i]/max*h,x=left+i*gap+(gap-bar)/2,y=top+h-bh;ctx.fillStyle=palette[i%palette.length];ctx.fillRect(x,y,bar,bh);ctx.fillStyle=css('--text')||'#183247';ctx.font='700 11px Ubuntu Sans,Arial';ctx.textAlign='center';ctx.fillText(String(values[i]),x+bar/2,Math.max(15,y-5));ctx.save();ctx.translate(x+bar/2,height-bottom+10);ctx.rotate(-Math.PI/5);ctx.font='10px Ubuntu Sans,Arial';ctx.textAlign='right';ctx.fillStyle=css('--muted')||'#657b8c';ctx.fillText(String(label).slice(0,18),0,0);ctx.restore();});
  }
  function doughnut(id,chart){const box=setup(id);if(!box)return;const{ctx,width,height}=box,labels=chart?.labels||[],values=(chart?.values||[]).map(Number),total=values.reduce((a,b)=>a+b,0);if(!total)return empty(box);const cx=width*.34,cy=height*.5,r=Math.min(width*.23,height*.35),inner=r*.58;let start=-Math.PI/2;values.forEach((v,i)=>{const end=start+v/total*Math.PI*2;ctx.beginPath();ctx.arc(cx,cy,r,start,end);ctx.arc(cx,cy,inner,end,start,true);ctx.closePath();ctx.fillStyle=palette[i%palette.length];ctx.fill();start=end;});ctx.fillStyle=css('--text')||'#183247';ctx.textAlign='center';ctx.font='700 25px Exo 2,Arial';ctx.fillText(String(total),cx,cy+5);let x=width*.61,y=Math.max(30,height*.22);labels.forEach((label,i)=>{ctx.fillStyle=palette[i%palette.length];ctx.fillRect(x,y-8,11,11);ctx.fillStyle=css('--text')||'#183247';ctx.textAlign='left';ctx.font='11px Ubuntu Sans,Arial';ctx.fillText(String(label).slice(0,24),x+18,y);ctx.font='700 11px Ubuntu Sans,Arial';ctx.fillText(String(values[i]||0),x+18,y+17);y+=43;});}
  function render(){bars('report-pipeline',data.pipeline);doughnut('report-quotes',data.quotes);bars('report-leads',data.leads);}
  let timer;window.addEventListener('resize',()=>{clearTimeout(timer);timer=setTimeout(render,120)});document.querySelector('[data-theme-toggle]')?.addEventListener('click',()=>setTimeout(render,80));render();
})();

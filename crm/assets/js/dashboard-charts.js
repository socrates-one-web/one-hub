(() => {
  const source = document.getElementById('dashboard-chart-data');
  if (!source) return;
  let data = {};
  try { data = JSON.parse(source.textContent || '{}'); } catch (_) { return; }

  const palette = ['#0074c7','#ff5300','#73bc25','#f6bd00','#31b4e7','#6f42c1','#d84343','#005b96','#00a6a6','#9c6b30','#4f6d7a','#c44536'];

  function setup(canvas) {
    if (!canvas) return null;
    const ratio = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    const width = Math.max(300, Math.floor(rect.width));
    const height = Math.max(220, Math.floor(rect.height));
    canvas.width = width * ratio;
    canvas.height = height * ratio;
    const ctx = canvas.getContext('2d');
    ctx.scale(ratio, ratio);
    ctx.clearRect(0,0,width,height);
    return {ctx,width,height};
  }

  function themeText() {
    return getComputedStyle(document.documentElement).getPropertyValue('--text').trim() || '#183247';
  }
  function themeMuted() {
    return getComputedStyle(document.documentElement).getPropertyValue('--muted').trim() || '#657b8c';
  }
  function themeLine() {
    return getComputedStyle(document.documentElement).getPropertyValue('--line').trim() || '#d8e3ea';
  }

  function noData(ctx,width,height) {
    ctx.fillStyle = themeMuted();
    ctx.font = '14px Ubuntu Sans, Arial';
    ctx.textAlign = 'center';
    ctx.fillText('Sin datos registrados todavía', width/2, height/2);
  }

  function drawDoughnut(id, chartData) {
    const canvas = document.getElementById(id);
    const box = setup(canvas); if (!box) return;
    const {ctx,width,height}=box;
    const values=(chartData?.values||[]).map(Number);
    const labels=chartData?.labels||[];
    const total=values.reduce((a,b)=>a+b,0);
    if (!total) return noData(ctx,width,height);
    const cx=width*.34, cy=height*.5, radius=Math.min(width*.23,height*.35), inner=radius*.58;
    let start=-Math.PI/2;
    values.forEach((value,i)=>{
      const end=start+(value/total)*Math.PI*2;
      ctx.beginPath();ctx.arc(cx,cy,radius,start,end);ctx.arc(cx,cy,inner,end,start,true);ctx.closePath();
      ctx.fillStyle=palette[i%palette.length];ctx.fill();
      start=end;
    });
    ctx.fillStyle=themeText();ctx.textAlign='center';ctx.font='700 26px Exo 2, Arial';ctx.fillText(String(total),cx,cy+4);
    ctx.font='12px Ubuntu Sans, Arial';ctx.fillStyle=themeMuted();ctx.fillText('Total',cx,cy+23);
    const x=width*.61; let y=Math.max(35,height*.27);
    labels.forEach((label,i)=>{
      ctx.fillStyle=palette[i%palette.length];ctx.fillRect(x,y-9,12,12);
      ctx.fillStyle=themeText();ctx.textAlign='left';ctx.font='12px Ubuntu Sans, Arial';ctx.fillText(String(label),x+20,y);
      ctx.font='700 12px Ubuntu Sans, Arial';ctx.fillText(String(values[i]||0),x+20,y+18);
      y+=52;
    });
  }

  function drawBars(id, chartData, horizontal=false) {
    const canvas=document.getElementById(id); const box=setup(canvas); if(!box)return;
    const {ctx,width,height}=box; const labels=chartData?.labels||[]; const values=(chartData?.values||[]).map(Number);
    const max=Math.max(0,...values); if(!max)return noData(ctx,width,height);
    const left=horizontal?115:45,right=20,top=24,bottom=horizontal?20:56;
    const plotW=width-left-right,plotH=height-top-bottom;
    ctx.strokeStyle=themeLine();ctx.lineWidth=1;
    for(let i=0;i<=4;i++){const y=top+plotH*i/4;ctx.beginPath();ctx.moveTo(left,y);ctx.lineTo(width-right,y);ctx.stroke();}
    if(horizontal){
      const rowH=plotH/Math.max(labels.length,1);
      labels.forEach((label,i)=>{
        const y=top+i*rowH+rowH*.18; const h=Math.max(10,rowH*.5); const w=(values[i]/max)*plotW;
        ctx.fillStyle=palette[i%palette.length];ctx.fillRect(left,y,w,h);
        ctx.fillStyle=themeText();ctx.font='11px Ubuntu Sans, Arial';ctx.textAlign='right';ctx.fillText(String(label).slice(0,18),left-8,y+h*.72);
        ctx.textAlign='left';ctx.font='700 11px Ubuntu Sans, Arial';ctx.fillText(String(values[i]),left+w+6,y+h*.72);
      });
    } else {
      const gap=plotW/Math.max(labels.length,1); const barW=Math.min(52,gap*.58);
      labels.forEach((label,i)=>{
        const h=(values[i]/max)*plotH; const x=left+i*gap+(gap-barW)/2; const y=top+plotH-h;
        ctx.fillStyle=palette[i%palette.length];ctx.fillRect(x,y,barW,h);
        ctx.fillStyle=themeText();ctx.font='700 11px Ubuntu Sans, Arial';ctx.textAlign='center';ctx.fillText(String(values[i]),x+barW/2,Math.max(14,y-6));
        ctx.save();ctx.translate(x+barW/2,height-bottom+10);ctx.rotate(-Math.PI/5);ctx.font='10px Ubuntu Sans, Arial';ctx.textAlign='right';ctx.fillStyle=themeMuted();ctx.fillText(String(label).slice(0,16),0,0);ctx.restore();
      });
    }
  }

  function drawLines(id, chartData) {
    const canvas=document.getElementById(id); const box=setup(canvas); if(!box)return;
    const {ctx,width,height}=box; const labels=chartData?.labels||[]; const sets=chartData?.datasets||[];
    const all=sets.flatMap(s=>(s.values||[]).map(Number)); const max=Math.max(0,...all);
    if(!max)return noData(ctx,width,height);
    const left=50,right=18,top=35,bottom=48,plotW=width-left-right,plotH=height-top-bottom;
    ctx.strokeStyle=themeLine();ctx.lineWidth=1;ctx.fillStyle=themeMuted();ctx.font='10px Ubuntu Sans, Arial';ctx.textAlign='right';
    for(let i=0;i<=4;i++){const value=max*(1-i/4),y=top+plotH*i/4;ctx.beginPath();ctx.moveTo(left,y);ctx.lineTo(width-right,y);ctx.stroke();ctx.fillText(Math.round(value).toLocaleString(),left-7,y+3);}
    labels.forEach((label,i)=>{const x=left+(plotW*(i/Math.max(labels.length-1,1)));ctx.fillStyle=themeMuted();ctx.textAlign='center';ctx.fillText(String(label),x,height-19);});
    sets.forEach((set,si)=>{
      const vals=(set.values||[]).map(Number);ctx.strokeStyle=palette[si%palette.length];ctx.lineWidth=2.5;ctx.beginPath();
      vals.forEach((v,i)=>{const x=left+plotW*(i/Math.max(vals.length-1,1)),y=top+plotH-(v/max)*plotH;i?ctx.lineTo(x,y):ctx.moveTo(x,y);});
      ctx.stroke();vals.forEach((v,i)=>{const x=left+plotW*(i/Math.max(vals.length-1,1)),y=top+plotH-(v/max)*plotH;ctx.fillStyle=palette[si%palette.length];ctx.beginPath();ctx.arc(x,y,3.5,0,Math.PI*2);ctx.fill();});
      const lx=left+si*110;ctx.fillStyle=palette[si%palette.length];ctx.fillRect(lx,8,12,12);ctx.fillStyle=themeText();ctx.textAlign='left';ctx.font='11px Ubuntu Sans, Arial';ctx.fillText(String(set.label||''),lx+18,18);
    });
  }

  function render() {
    drawDoughnut('chart-expiration',data.expiration);
    drawDoughnut('chart-quotes',data.quotes);
    drawLines('chart-revenue',data.revenue);
    drawBars('chart-countries',data.countries,true);
    drawDoughnut('chart-contracts',data.contracts);
  }

  let timer;
  window.addEventListener('resize',()=>{clearTimeout(timer);timer=setTimeout(render,120);});
  document.querySelector('[data-theme-toggle]')?.addEventListener('click',()=>setTimeout(render,80));
  render();
})();
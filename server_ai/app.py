"""
LifeKaFunda Self-Hosted AI Background Removal Microservice
Uses Rembg (U2Net / RMBG) for 100% Free, Studio-Quality 1024px Cutouts
"""
import io
import uvicorn
from fastapi import FastAPI, Request, Response
from fastapi.middleware.cors import CORSMiddleware
from rembg import remove, new_session
from PIL import Image

app = FastAPI(title="LifeKaFunda Self-Hosted AI Background Remover")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize high-quality U2Net segmentation session
session = new_session("u2net")

@app.get("/health")
def health():
    return {"status": "ok", "engine": "rembg_u2net"}

@app.post("/api/remove-bg")
async def remove_background(request: Request):
    data = await request.body()
    if not data:
        return Response(content="Empty body", status_code=400)
    
    try:
        input_image = Image.open(io.BytesIO(data))
        output_image = remove(input_image, session=session)
        
        output_buffer = io.BytesIO()
        output_image.save(output_buffer, format="PNG")
        png_bytes = output_buffer.getvalue()
        
        return Response(content=png_bytes, media_type="image/png")
    except Exception as e:
        return Response(content=str(e), status_code=500)

if __name__ == "__main__":
    print("=================================================================")
    print(" LifeKaFunda Self-Hosted AI Server running on http://127.0.0.1:5005")
    print("=================================================================")
    uvicorn.run(app, host="127.0.0.1", port=5005)
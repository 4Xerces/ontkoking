import { useRef } from 'react';

import { Canvas } from '@react-three/fiber';
import { Model } from './model';
import { Bounds, OrbitControls } from '@react-three/drei';



function App() {
   return (
    <div 
      style={{
        position: 'fixed',  
        top: 0,
        left: 0,
        width: '100vw',     
        height: '100vh',    
        overflow: 'hidden', 
        zIndex: 1,
      }}
    >
          <Canvas  camera={{
        fov: 60,              // Field of View
        near: 0.1,            // Near clipping plane
        far: 500,            // Far clipping plane
        position: [15, 8, 2]   // [X, Y, Z] starting position
      }}
    >
      
        <ambientLight intensity={0.5} />
        <directionalLight position={[8, 5, 10]} intensity={1.5} />
        
        <Bounds>
           <Model />
        </Bounds>

        <OrbitControls enableZoom={false} target={[0, 4, 0]} />
       
      </Canvas>
    </div>
  );
}


export default App

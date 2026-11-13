import { useRef } from 'react';
import { Canvas, useFrame, useThree } from '@react-three/fiber';
import { Model } from './model';
import { Bounds } from '@react-three/drei';
import {
  ChromaticAberration,
  EffectComposer,
  Noise,
  Pixelation,
  Vignette,
} from '@react-three/postprocessing';

const CAMERA_POSITION = [5, 12, 0];
const CAMERA_TARGET = [-10, 8, 0];
// peep 100 - 130 ivm 4de muur POTENTIALLY THO
const CAMERA_TURN_RANGE = (100 * Math.PI) / 180;
const CAMERA_TILT_RANGE = 0.25;
const CAMERA_SMOOTHING = 6;

function CursorCamera({ pointer }) {
  const { camera } = useThree();
  const offset = CAMERA_POSITION.map((value, index) => value - CAMERA_TARGET[index]);
  const radius = Math.hypot(...offset);
  const initialTheta = Math.atan2(offset[0], offset[2]);
  const initialPhi = Math.acos(offset[1] / radius);
  const angles = useRef({ theta: initialTheta, phi: initialPhi });

  useFrame((_, delta) => {
    const smoothing = 1 - Math.exp(-CAMERA_SMOOTHING * delta);
    const theta = initialTheta - pointer.current.x * (CAMERA_TURN_RANGE / 2);
    const phi = initialPhi + pointer.current.y * CAMERA_TILT_RANGE;

    angles.current.theta += (theta - angles.current.theta) * smoothing;
    angles.current.phi += (phi - angles.current.phi) * smoothing;

    const sinPhi = Math.sin(angles.current.phi);
    camera.position.set(
      CAMERA_TARGET[0] + radius * sinPhi * Math.sin(angles.current.theta),
      CAMERA_TARGET[1] + radius * Math.cos(angles.current.phi),
      CAMERA_TARGET[2] + radius * sinPhi * Math.cos(angles.current.theta),
    );
    camera.lookAt(...CAMERA_TARGET);
  });

  return null;
}

function App() {
  const pointer = useRef({ x: 0, y: 0 });

  function updatePointer(event) {
    const bounds = event.currentTarget.getBoundingClientRect();
    pointer.current.x = ((event.clientX - bounds.left) / bounds.width) * 2 - 1;
    pointer.current.y = ((event.clientY - bounds.top) / bounds.height) * 2 - 1;
  }

  function resetPointer() {
    pointer.current.x = 0;
    pointer.current.y = 0;
  }

  return (
    <div
      onPointerMove={updatePointer}
      onPointerLeave={resetPointer}
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
      <Canvas camera={{ fov: 60, near: 0.1, far: 500, position: CAMERA_POSITION }}>
        <color attach="background" args={['#0c1020']} />
        <fog attach="fog" args={['#111522', 32, 90]} />
        <ambientLight color="#8492b8" intensity={0.7} />
        <directionalLight
          color="#b9c9ff"
          position={[8, 15, 10]}
          intensity={1.8}
        />
        <pointLight
          color="#ff704a"
          position={[-14, 10, 4]}
          intensity={25}
          distance={40}
          decay={2}
        />

        <Bounds>
          <Model />
        </Bounds>

        <CursorCamera pointer={pointer} />
        <EffectComposer multisampling={0}>
          <Pixelation granularity={2} />
          <Noise opacity={0.008} />
          <ChromaticAberration offset={[0.0004, 0.0004]} />
          <Vignette eskil={false} offset={0.18} darkness={0.45} />
        </EffectComposer>
      </Canvas>
    </div>
  );
}

export default App

import { useEffect, useRef } from 'react';
import { Canvas, useFrame, useThree } from '@react-three/fiber';
import { Model } from './model';
import { Bounds } from '@react-three/drei';
import { Vector3 } from 'three';
import {
  ChromaticAberration,
  EffectComposer,
  Noise,
  Pixelation,
  Vignette,
} from '@react-three/postprocessing';
import Recipes from './Recipes';

const CAMERA_POSITION = [5, 12, 0];
const CAMERA_TARGET = [-10, 8, 0];
const BOOK_POSITION = [-11.752, 4.82, 4.749];
const BOOK_CAMERA_POSITION = [-10.7, 11.41, 4.749];
// peep 100 - 130 ivm 4de muur POTENTIALLY THO
const CAMERA_TURN_RANGE = (100 * Math.PI) / 180;
const CAMERA_TILT_RANGE = 0.25;
const CAMERA_SMOOTHING = 6;
const BOOK_FOCUS_DURATION = 1.2;

function CursorCamera({ pointer, focus, onFocusComplete }) {
  const { camera } = useThree();
  const offset = CAMERA_POSITION.map((value, index) => value - CAMERA_TARGET[index]);
  const radius = Math.hypot(...offset);
  const initialTheta = Math.atan2(offset[0], offset[2]);
  const initialPhi = Math.acos(offset[1] / radius);
  const angles = useRef({ theta: initialTheta, phi: initialPhi });
  const initialTarget = useRef(new Vector3(...CAMERA_TARGET));
  const focusTarget = useRef(new Vector3(...BOOK_POSITION));
  const cameraTarget = useRef(new Vector3());
  const focusPosition = useRef(new Vector3(...BOOK_CAMERA_POSITION));
  const cameraFocus = useRef(null);

  useFrame((_, delta) => {
    if (focus.current) {
      if (!cameraFocus.current) {
        cameraFocus.current = {
          elapsed: 0,
          fromPosition: camera.position.clone(),
        };
      }
      const currentFocus = cameraFocus.current;

      currentFocus.elapsed += delta;
      const progress = Math.min(currentFocus.elapsed / BOOK_FOCUS_DURATION, 1);
      const easedProgress = progress * progress * (3 - 2 * progress);
      camera.position.lerpVectors(
        currentFocus.fromPosition,
        focusPosition.current,
        easedProgress,
      );
      cameraTarget.current.lerpVectors(
        initialTarget.current,
        focusTarget.current,
        easedProgress,
      );
      camera.lookAt(cameraTarget.current);

      if (progress === 1 && !currentFocus.completed) {
        currentFocus.completed = true;
        onFocusComplete();
      }
      return;
    }

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

function KitchenScene() {
  const pointer = useRef({ x: 0, y: 0 });
  const focus = useRef(null);
  const navigationTimer = useRef(null);

  useEffect(() => () => window.clearTimeout(navigationTimer.current), []);

  function updatePointer(event) {
    if (focus.current) return;
    const bounds = event.currentTarget.getBoundingClientRect();
    pointer.current.x = ((event.clientX - bounds.left) / bounds.width) * 2 - 1;
    pointer.current.y = ((event.clientY - bounds.top) / bounds.height) * 2 - 1;
  }

  function resetPointer() {
    pointer.current.x = 0;
    pointer.current.y = 0;
  }

  function focusBook() {
    focus.current = true;
  }

  function navigateToRecipes() {
    navigationTimer.current = window.setTimeout(() => {
      window.location.assign('/recipes');
    }, 700);
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
          <Model onBookClick={focusBook} />
        </Bounds>

        <CursorCamera
          pointer={pointer}
          focus={focus}
          onFocusComplete={navigateToRecipes}
        />
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

function App() {
  const isRecipesPage = window.location.pathname.replace(/\/+$/, '') === '/recipes';
  return isRecipesPage ? <Recipes /> : <KitchenScene />;
}

export default App
